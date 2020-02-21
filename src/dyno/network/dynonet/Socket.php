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


class Socket
{
    /** @var resource */
    private $socket;

    /**
     * Socket constructor.
     * @param \ThreadedLogger $logger
     * @param int $port
     * @param string $interface
     */
    public function __construct(\ThreadedLogger $logger, int $port = 10102, string $interface = "0.0.0.0")
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (@socket_bind($this->socket, $interface, $port) !== true) {
            $logger->critical("**** FAILED TO BIND TO " . $interface . ":" . $port . "!");
            $logger->critical("Perhaps a server is already running on that port?");
            exit(1);
        }
        socket_listen($this->socket);
        $logger->info("Dyno is running on $interface:$port");
        socket_set_nonblock($this->socket);
    }

    /**
     * @return resource
     */
    public function getClient()
    {
        return socket_accept($this->socket);
    }

    /**
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }

    public function close()
    {
        socket_close($this->socket);
    }
}