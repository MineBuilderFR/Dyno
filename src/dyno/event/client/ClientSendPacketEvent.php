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
use dyno\event\Cancellable;
use dyno\network\packages\DataPacket;

class ClientSendPacketEvent extends ClientEvent implements Cancellable
{
    public static $handlerList = null;

    /** @var DataPacket */
    private $packet;

    /**
     * ClientSendPacketEvent constructor.
     * @param Client $client
     * @param DataPacket $packet
     */
    public function __construct(Client $client, DataPacket $packet)
    {
        parent::__construct($client);
        $this->packet = $packet;
    }

    /**
     * @return DataPacket
     */
    public function getPacket(): DataPacket
    {
        return $this->packet;
    }
}