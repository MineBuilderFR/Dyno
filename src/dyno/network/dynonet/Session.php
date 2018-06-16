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

use dyno\event\TranslationContainer;
use dyno\network\binary\Binary;
use dyno\network\packages\Info;

class Session
{

    public const MAGIC_BYTES = "\x35\xac\x66\xbf";

    private $receiveBuffer = "";
    private $sendBuffer = "";
    /** @var SessionManager */
    private $sessionManager;
    /** @var resource */
    private $socket;
    /** @var string */
    private $ip;
    /** @var int */
    private $port;

    /**
     * Session constructor.
     * @param SessionManager $sessionManager
     * @param $socket
     */
    public function __construct(SessionManager $sessionManager, $socket)
    {
        $this->sessionManager = $sessionManager;
        $this->socket = $socket;
        socket_getpeername($this->socket, $address, $port);
        $this->ip = $address;
        $this->port = $port;
        $sessionManager->getServer()->getLogger()->notice(
        //Yep triple getServer
            $sessionManager
                ->getServer()
                ->getServer()
                ->getServer()->getLanguage()->translate(
                    new TranslationContainer("%dyno.client.preConnect", [
                        $this->ip, $this->port
                    ])
                ));
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->ip . ':' . $this->port;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return bool
     */
    public function update()
    {
        $err = socket_last_error($this->socket);
        socket_clear_error($this->socket);
        if ($err == 10057 or $err == 10054) {
            $this->sessionManager->getServer()->getLogger()->error("Client [$this->ip:$this->port] has disconnected unexpectedly");
            return false;
        } else {
            $data = @socket_read($this->socket, 65535, PHP_BINARY_READ);
            if ($data != "") {
                $this->receiveBuffer .= $data;
            }
            if ($this->sendBuffer != "") {
                socket_write($this->socket, $this->sendBuffer);
                $this->sendBuffer = "";
            }
            return true;
        }
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
        @socket_close($this->socket);
    }

    /**
     * @return string[]
     */
    public function readPackets(): array
    {
        $packets = [];
        if ($this->receiveBuffer !== '') {
            $offset = 0;
            $len = strlen($this->receiveBuffer);
            while ($offset < $len) {
                if ($offset > $len - 7) {
                    break;
                }
                $magic = Binary::readShort(substr($this->receiveBuffer, $offset, 2));
                if ($magic !== Info::PROTOCOL_MAGIC) {
                    throw new \RuntimeException('Magic does not match.');
                }
                $pid = $this->receiveBuffer{$offset + 2};
                $pkLen = Binary::readInt(substr($this->receiveBuffer, $offset + 3, 4));
                $offset += 7;

                if ($pkLen <= ($len - $offset)) {
                    $buf = $pid . substr($this->receiveBuffer, $offset, $pkLen);
                    $offset += $pkLen;

                    $packets[] = $buf;
                } else {
                    $offset -= 7;
                    break;
                }
            }
            if ($offset < $len) {
                $this->receiveBuffer = substr($this->receiveBuffer, $offset);
            } else {
                $this->receiveBuffer = '';
            }
        }

        return $packets;
    }

    public function writePacket($data)
    {
        @socket_write($this->socket, Binary::writeShort(Info::PROTOCOL_MAGIC));
        @socket_write($this->socket, $data{0});
        @socket_write($this->socket, Binary::writeInt(strlen($data) - 1));
        @socket_write($this->socket, substr($data, 1));
    }
}