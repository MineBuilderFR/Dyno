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

namespace dyno\network\binary;

use dyno\utils\UUID;

class NetworkBinaryStream extends BinaryStream
{
    public function getUUID(): UUID
    {
        $part1 = $this->getLInt();
        $part0 = $this->getLInt();
        $part3 = $this->getLInt();
        $part2 = $this->getLInt();
        return new UUID($part0, $part1, $part2, $part3);
    }

    public function putUUID(UUID $uuid): void
    {
        $this->putLInt($uuid->getPart(1));
        $this->putLInt($uuid->getPart(0));
        $this->putLInt($uuid->getPart(3));
        $this->putLInt($uuid->getPart(2));
    }

    public function getByteRotation(): float
    {
        return (float)($this->getByte() * (360 / 256));
    }

    public function putByteRotation(float $rotation): void
    {
        $this->putByte((int)($rotation / (360 / 256)));
    }

    public function getString(): string
    {
        return $this->get($this->getUnsignedVarInt());
    }

    public function putString(string $v): void
    {
        $this->putUnsignedVarInt(strlen($v));
        $this->put($v);
    }
}