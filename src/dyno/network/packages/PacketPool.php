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

use dyno\network\packages\executor\{
    inputPacket, outputPacket
};

class PacketPool
{

    /** @var \SplFixedArray<DataPacket> */
    protected static $pool = null;

    public static function init()
    {
        self::registerPacket(new HeartbeatPacket());
        self::registerPacket(new ConnectPacket());
        self::registerPacket(new DisconnectPacket());
        self::registerPacket(new InformationPacket());
        self::registerPacket(new inputPacket());
        self::registerPacket(new outputPacket());
    }

    /**
     * @param DataPacket $packet
     */
    public static function registerPacket(DataPacket $packet)
    {
        static::$pool[$packet->pid()] = clone $packet;
    }

    /**
     * @param string $buffer
     * @return DataPacket
     */
    public static function getPacket(string $buffer): DataPacket
    {
        $pk = static::getPacketById(ord($buffer{0}));
        $pk->setBuffer($buffer);
        return $pk;
    }

    /**
     * @param int $pid
     * @return DataPacket
     */
    public static function getPacketById(int $pid): DataPacket
    {
        return isset(static::$pool[$pid]) ? clone static::$pool[$pid] : new UnknownPacket();
    }
}