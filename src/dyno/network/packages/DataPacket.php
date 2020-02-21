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

namespace dyno\network\packages;

use dyno\network\binary\NetworkBinaryStream;
use dyno\utils\Utils;

abstract class DataPacket extends NetworkBinaryStream
{

    public const NETWORK_ID = 0;
    /** @var bool */
    public $isEncoded = false;
    /** @var int */
    public $senderSubId = 0;
    /** @var int */
    public $recipientSubId = 0;

    public function pid()
    {
        return $this::NETWORK_ID;
    }

    public function getName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function canBeBatched(): bool
    {
        return true;
    }

    public function canBeSentBeforeLogin(): bool
    {
        return false;
    }

    /**
     * Returns whether the packet may legally have unread bytes left in the buffer.
     * @return bool
     */
    public function mayHaveUnreadBytes(): bool
    {
        return false;
    }

    public function decode()
    {
        $this->offset = 0;
        $this->decodeHeader();
        $this->decodePayload();
    }

    protected function decodeHeader()
    {
        $pid = $this->getUnsignedVarInt();
        assert($pid === static::NETWORK_ID);

        $this->senderSubId = $this->getByte();
        $this->recipientSubId = $this->getByte();
        assert($this->senderSubId === 0 and $this->recipientSubId === 0, "Got unexpected non-zero split-screen bytes (byte1: $this->senderSubId, byte2: $this->recipientSubId");
    }

    /**
     * Note for plugin developers: If you're adding your own packets, you should perform decoding in here.
     */
    protected function decodePayload() { }

    public function encode()
    {
        $this->reset();
        $this->encodeHeader();
        $this->encodePayload();
        $this->isEncoded = true;
    }

    protected function encodeHeader()
    {
        $this->putUnsignedVarInt(static::NETWORK_ID);

        $this->putByte($this->senderSubId);
        $this->putByte($this->recipientSubId);
    }

    /**
     * Note for plugin developers: If you're adding your own packets, you should perform encoding in here.
     */
    protected function encodePayload() { }

    public function clean()
    {
        $this->buffer = null;
        $this->isEncoded = false;
        $this->offset = 0;
        return $this;
    }

    public function __debugInfo()
    {
        $data = [];
        foreach ($this as $k => $v) {
            if ($k === "buffer" and is_string($v)) {
                $data[$k] = bin2hex($v);
            } elseif (is_string($v) or (is_object($v) and method_exists($v, "__toString"))) {
                $data[$k] = Utils::printable((string)$v);
            } else {
                $data[$k] = $v;
            }
        }

        return $data;
    }
}