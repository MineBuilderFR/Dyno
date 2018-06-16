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
 * @copyright (c) 2018
 * @author Y&SS-MineBuilderFR
 */

namespace dyno\network\dynonet;

use dyno\Client;
use dyno\exception\PacketException\OutputReceivedException;
use dyno\network\packages\{
    DataPacket, PacketPool
};
use dyno\Server;

class DynoInterface
{
    /** @var Server */
    private $server;
    /** @var string */
    private $ip;
    /** @var int */
    private $port;
    /** @var Client[] */
    private $clients;
    /** @var DynoServer */
    private $interface;

    /**
     * DynoInterface constructor.
     * @param Server $server
     * @param string $ip
     * @param int $port
     * @throws \Exception
     */
    public function __construct(Server $server, string $ip, int $port)
    {
        $this->server = $server;
        $this->ip = $ip;
        $this->port = $port;
        PacketPool::init();
        $this->interface = new DynoServer($server->getLogger(), $this, $server->getLoader(), $port, $ip);
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * @param Client $client
     */
    public function removeClient(Client $client)
    {
        $this->interface->addExternalClientCloseRequest($client->getHash());
        unset($this->clients[$client->getHash()]);
    }

    /**
     * @param Client $client
     * @param DataPacket $pk
     */
    public function putPacket(Client $client, DataPacket $pk)
    {
        if (!$pk->isEncoded) {
            $pk->encode();
        }
        $this->interface->pushMainToThreadPacket($client->getHash() . "|" . $pk->buffer);
    }

    /**
     * @throws OutputReceivedException
     */
    public function process()
    {
        $this->processOpenClients();
        $this->processPackets();
        $this->processCloseClients();
    }

    public function processOpenClients()
    {
        while (strlen($data = $this->interface->getClientOpenRequest()) > 0) {
            $tmp = explode(":", $data);
            $this->addClient($tmp[0], $tmp[1]);
        }
    }

    /**
     * @param string $ip
     * @param int $port
     */
    public function addClient(string $ip, int $port)
    {
        $this->clients[$ip . ":" . $port] = new Client($this, $ip, $port);
    }

    /**
     * @throws OutputReceivedException
     */
    public function processPackets()
    {
        while (strlen($data = $this->interface->readThreadToMainPacket()) > 0) {
            $tmp = explode("|", $data, 2);
            if (count($tmp) == 2) {
                $this->handlePacket($tmp[0], $tmp[1]);
            }
        }
    }

    /**
     * @param $hash
     * @param $buffer
     * @throws OutputReceivedException
     */
    public function handlePacket($hash, $buffer)
    {
        if (!isset($this->clients[$hash])) {
            return;
        }
        $client = $this->clients[$hash];
        if (($pk = PacketPool::getPacket($buffer)) != null) {
            $pk->decode();
            $client->handleDataPacket($pk);
        } else {
            $this->server->getLogger()->critical("Error packet");
        }
    }

    public function processCloseClients()
    {
        while (strlen($data = $this->interface->getInternalClientCloseRequest()) > 0) {
            $this->server->removeClient($this->clients[$data]);
            unset($this->clients[$data]);
        }
    }
}