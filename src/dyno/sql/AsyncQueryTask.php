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

use dyno\task\AsyncTask;

abstract class AsyncQueryTask extends AsyncTask
{

    /** @var MysqlCredentials */
    private $credentials;

    /**
     * AsyncQueryTask constructor.
     * @param MysqlCredentials $credentials
     */
    public function __construct(MysqlCredentials $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * @return \mysqli|null
     */
    protected function getMysqli(): ?\mysqli
    {
        $mysqli = MySQLDatabase::createSql($this->credentials);
        return $mysqli;
    }
}