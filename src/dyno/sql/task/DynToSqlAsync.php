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

namespace dyno\sql\task;

use dyno\exception\{
    BaseException\NoBaseExistException, TableException\InvalidTableException
};
use dyno\manager\Tables;
use dyno\sql\{
    AsyncQueryTask, MysqlDatabase
};

class DynToSqlAsync extends AsyncQueryTask
{

    /** @var string */
    private $database;
    /** @var string */
    private $baseTable;

    /**
     * DynToSqlAsync constructor.
     * @param MysqlDatabase $database
     * @param Tables $tables
     * @throws NoBaseExistException
     * @throws InvalidTableException
     */
    public function __construct(MysqlDatabase $database, Tables $tables)
    {
        $baseTable = array();
        $this->database = $database->getCredentials()->database;
        foreach ($tables->getAllBasesName() as $baseName) {
            foreach ($tables->getAllTablesName($baseName) as $table) {
                array_push($baseTable, serialize(array(
                    $baseName => serialize([
                        "table" => $table,
                        "data" => $tables->getTable($baseName, $table)->getAllKeyValue()
                    ])
                )));
            }
        }
        $this->baseTable = serialize($baseTable);
        parent::__construct($database->getCredentials());
    }

    public function onRun()
    {
        $db = $this->getMysqli();

        foreach (unserialize($this->baseTable) as $baseTable) {
            $baseTable = unserialize($baseTable);
            /**
             * @var string $base
             * @var string[] $table
             */
            foreach ($baseTable as $base => $table) {
                $table = unserialize($table);
                $base = "dyno-" . $base;
                $data = json_encode($table["data"]);
                $realTable = $table["table"];
                $db->query("DROP TABLE IF EXISTS `{$db->escape_string($base)}`");

                $db->query("CREATE TABLE IF NOT EXISTS `{$db->escape_string($base)}`  (
                      `tableName` VARCHAR(255),
                      `data` MEDIUMTEXT,
                      PRIMARY KEY (tableName)
                    ) DEFAULT CHARSET=utf8");

                $db->query("INSERT INTO `{$db->escape_string($base)}`
                      (tableName, data)
                        VALUES
                      ('{$db->escape_string($realTable)}', '{$db->escape_string($data)}')
                     ");
            }
        }
    }
}