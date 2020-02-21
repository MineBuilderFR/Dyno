<?php
/**
 * ________
 * ___  __ \____  ______________
 * __  / / /_  / / /_  __ \  __ \
 * _  /_/ /_  /_/ /_  / / / /_/ /
 * /_____/ _\__, / /_/ /_/\____/
 *         /____/
 *
 * This program is free: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is based on PocketMine Software and Synapse.
 *
 * @copyright (c) 2020
 * @author Y&SS-YassLV
 */

namespace dyno\network\dynonet;


class SessionManager
{
    /** @var bool */
    protected $shutdown = false;
    /** @var DynoServer */
    protected $server;
    /** @var Socket */
    protected $socket;
    /** @var Session[] */
    private $sessions = [];

    /**
     * SessionManager constructor.
     * @param DynoServer $server
     * @param Socket $socket
     */
    public function __construct(DynoServer $server, Socket $socket)
    {
        $this->server = $server;
        $this->socket = $socket;
        $this->run();
    }

    public function run()
    {
        $this->tickProcessor();
    }

    private function tickProcessor()
    {
        while (!$this->server->isShutdown()) {
            $start = microtime(true);
            $this->tick();
            $time = microtime(true);
            if ($time - $start < 0.01) {
                @time_sleep_until($time + 0.01 - ($time - $start));
            }
        }
        $this->tick();
        foreach ($this->sessions as $client) {
            $client->close();
        }
        $this->socket->close();
    }

    private function tick()
    {
        try {
            while (($socket = $this->socket->getClient())) {
                $session = new Session($this, $socket);
                $this->sessions[$session->getHash()] = $session;
                $this->server->addClientOpenRequest($session->getHash());
            }

            while (strlen($data = $this->server->readMainToThreadPacket()) > 0) {
                $tmp = explode("|", $data, 2);
                if (count($tmp) == 2) {
                    if (isset($this->sessions[$tmp[0]])) {
                        $this->sessions[$tmp[0]]->writePacket($tmp[1]);
                    }
                }
            }

            foreach ($this->sessions as $session) {
                if ($session->update()) {
                    while (!empty($data = $session->readPackets())) {
                        foreach ($data as $finalData) {
                            $this->server->pushThreadToMainPacket($session->getHash() . "|" . $finalData);
                        }
                    }
                } else {
                    $session->close();
                    $this->server->addInternalClientCloseRequest($session->getHash());
                    unset($this->sessions[$session->getHash()]);
                }
            }

            while (strlen($data = $this->server->getExternalClientCloseRequest()) > 0) {
                $this->sessions[$data]->close();
                unset($this->sessions[$data]);
            }
        } catch (\Throwable $e) {
            $this->server->getLogger()->logException($e);
        }
    }

    /**
     * @return Session[]
     */
    public function getClients(): array
    {
        return $this->sessions;
    }

    /**
     * @return DynoServer
     */
    public function getServer(): DynoServer
    {
        return $this->server;
    }
}