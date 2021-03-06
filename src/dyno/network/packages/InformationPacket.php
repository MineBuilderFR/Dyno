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

class InformationPacket extends DataPacket
{
    public const NETWORK_ID = Info::INFORMATION_PACKET;
    public const TYPE_LOGIN = 0;
    public const TYPE_CLIENT_DATA = 1;
    public const TYPE_PLUGIN_MESSAGE = 2;
    public const INFO_LOGIN_SUCCESS = 'success';
    public const INFO_LOGIN_FAILED = 'failed';

    /** @var int */
    public $type;
    /** @var string */
    public $message;

    protected function encodePayload()
    {
        $this->putByte($this->type);
        $this->putString($this->message);
    }

    protected function decodePayload()
    {
        $this->type = $this->getByte();
        $this->message = $this->getString();
    }
}