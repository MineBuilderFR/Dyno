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

class ConnectPacket extends DataPacket
{
    public const NETWORK_ID = Info::CONNECT_PACKET;

    /** @var int */
    public $protocol = Info::CURRENT_PROTOCOL;
    /** @var string */
    public $description;
    /** @var string */
    public $password;

    protected function encodePayload()
    {
        $this->putInt($this->protocol);
        $this->putString($this->description);
        $this->putString($this->password);
    }

    protected function decodePayload()
    {
        $this->protocol = $this->getInt();
        $this->description = $this->getString();
        $this->password = $this->getString();
    }
}