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

namespace dyno\network\packages;


class UnknownPacket extends DataPacket
{

    public const NETWORK_ID = -1; //Invalid, do not try to write this
    /** @var string */
    public $payload;

    public function pid()
    {
        if (strlen($this->payload ?? "") > 0) {
            return ord($this->payload{0});
        }
        return self::NETWORK_ID;
    }

    public function getName(): string
    {
        return "unknown packet";
    }

    public function decode()
    {
        $this->payload = $this->getRemaining();
    }

    public function encode()
    {
        //Do not reset the buffer, this class does not have a valid NETWORK_ID constant.
        $this->put($this->payload);
    }
}