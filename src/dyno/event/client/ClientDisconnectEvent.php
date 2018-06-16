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

namespace dyno\event\client;

use dyno\Client;

class ClientDisconnectEvent extends ClientEvent
{
    public static $handlerList = null;

    /** @var string */
    private $reason;
    /** @var int */
    private $type;

    /**
     * ClientDisconnectEvent constructor.
     * @param Client $client
     * @param string $reason
     * @param int $type
     */
    public function __construct(Client $client, string $reason, int $type)
    {
        parent::__construct($client);
        $this->reason = $reason;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     */
    public function setReason(string $reason)
    {
        $this->reason = $reason;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }
}