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

namespace dyno\sql\task;

use dyno\event\TranslationContainer;
use dyno\exception\{
    BaseException\NoBaseExistException, TableException\InvalidTableException
};
use dyno\Server;
use dyno\task\Task;

class AutoDynToSqlTask extends Task
{
    /** @var Server */
    private $server;
    /** @var int */
    private $sec = 0;
    /** @var int */
    private $secReset;
    /** @var bool */
    private $log;

    /**
     * AutoDynToSqlTask constructor.
     * @param Server $server
     * @param int $secReset
     * @param bool $log
     */
    public function __construct(Server $server, int $secReset, bool $log)
    {
        $this->server = $server;
        $this->secReset = $secReset;
        $this->log = $log;
    }

    /**
     * @param int $currentTick
     * @throws NoBaseExistException
     * @throws InvalidTableException
     */
    public function onRun(int $currentTick)
    {
        if ($this->sec >= $this->secReset) {
            $this->server->getScheduler()->scheduleAsyncTask(new DynToSqlAsync(
                $this->server->getDataBase(),
                $this->server->getTables()
            ));
            if ($this->log === true) {
                $this->server->getLogger()->debug("DynToSQL > " .
                    $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.mysql.connection.logAutoDynToSql")
                    )
                );
            }
            $this->sec = 0;
        }
        $this->sec++;
    }
}