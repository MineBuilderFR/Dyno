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

namespace dyno\sql;

use dyno\utils\Config;

class MysqlCredentials
{
    /** @var string */
    public $host;
    /** @var string */
    public $port;
    /** @var string */
    public $username;
    /** @var string */
    public $password;
    /** @var string */
    public $database;
    /** @var string */
    public $socket;

    /**
     * @param Config $properties
     * @return MysqlCredentials
     */
    public static function initProperties(Config $properties): MysqlCredentials
    {
        $credential = new MysqlCredentials;
        $credential->host = (string)$properties->getNested("connection-host", "localhost");
        $credential->port = (int)$properties->getNested("connection-port", "3306");
        $credential->username = (string)$properties->getNested("connection-username", "root");
        $credential->password = (string)$properties->getNested("connection-password", "");
        $credential->database = (string)$properties->getNested("connection-database", "dyno");
        $credential->socket = (string)$properties->getNested("connection-socket", "");
        return $credential;
    }

}