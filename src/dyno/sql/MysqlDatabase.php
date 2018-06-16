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

namespace dyno\sql;

use dyno\event\TranslationContainer;
use dyno\exception\Mysql\MysqlConnectException;
use dyno\Server;

class MysqlDatabase
{
    /** @var \mysqli */
    public static $mysql;
    /** @var Server */
    private $server;
    /** @var MysqlCredentials */
    private $credentials;

    /**
     * MysqlDatabase constructor.
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
        $properties = $server->getMysqlProperties();
        $this->credentials = MysqlCredentials::initProperties($properties);
        self::createSql($this->credentials);
    }


    /**
     * @param MysqlCredentials $credentials
     * @return \mysqli
     * @throws MysqlConnectException
     */
    public static function createSql(MysqlCredentials $credentials): \mysqli
    {
        if (self::$mysql === null) {
            self::$mysql = @new \mysqli(
                $credentials->host, $credentials->username,
                $credentials->password, $credentials->database,
                $credentials->port, $credentials->socket
            );
            if (self::$mysql->connect_error) {
                throw new MysqlConnectException(Server::getInstance()->getLanguage()->translate(
                        new TranslationContainer("%dyno.mysql.connection.failure")
                    ) . ":" . self::$mysql->connect_error);
            }
        }
        return self::$mysql;
    }

    /**
     * @return MysqlCredentials
     */
    public function getCredentials(): MysqlCredentials
    {
        return $this->credentials;
    }

    public function close()
    {
        if (self::$mysql !== null) {
            self::$mysql->close();
            $this->server->getLogger()->info("Mysql database closed");
        }
    }
}