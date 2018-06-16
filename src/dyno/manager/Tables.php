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

namespace dyno\manager;

use dyno\event\TranslationContainer;
use dyno\exception\{
    BaseException\NoBaseExistException, TableException\NoTableExistException, TableException\TableAlreadyExistException
};
use dyno\task\FileWriteTask;

class Tables extends Bases
{

    /** @var bool */
    private $asyncFileWrite = false;

    /**
     * @param bool $value
     * @return Tables
     */
    public function setAsyncFileWrite(bool $value): self
    {
        $this->asyncFileWrite = $value;
        return $this;
    }

    /**
     * @param string $baseName
     * @param string $tableName
     * @throws NoBaseExistException
     * @throws TableAlreadyExistException
     */
    public function createTable(string $baseName, string $tableName)
    {
        $baseName = Utils::clean($baseName);
        $tableName = Utils::clean($tableName);
        if (!$this->baseNameExist($baseName)) {
            throw new NoBaseExistException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.base.noexist", [
                    $baseName
                ])
            ));
        }
        if (file_exists(($table = $this->dataPathBases . $baseName . DIRECTORY_SEPARATOR . $tableName . Utils::DYNO_EXTENSION))) {
            throw new TableAlreadyExistException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.table.alreadyexist", [
                    $tableName
                ])
            ));
        }
        if ($this->asyncFileWrite == true) {
            $this->server->getScheduler()->scheduleAsyncTask(
                new FileWriteTask($table, json_encode([]))
            );
        } else {
            file_put_contents($table, json_encode([]), JSON_PRETTY_PRINT);
        }
    }

    /**
     * @param string $baseName
     * @param string $tableName
     * @throws NoBaseExistException
     * @throws NoTableExistException
     */
    public function removeTable(string $baseName, string $tableName)
    {
        $baseName = Utils::clean($baseName);
        $tableName = Utils::clean($tableName);
        if (!$this->baseNameExist($baseName)) {
            throw new NoBaseExistException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.base.noexist", [
                    $baseName
                ])
            ));
        }
        if (!file_exists(($table = $this->dataPathBases . $baseName . DIRECTORY_SEPARATOR . $tableName . Utils::DYNO_EXTENSION))) {
            throw new NoTableExistException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.table.notableexist", [
                    $tableName
                ])
            ));
        }
        unlink($table);
    }

    /**
     * @param string $baseName
     * @param string $tableName
     * @return bool
     */
    public function tableNameExist(string $baseName, string $tableName): bool
    {
        $baseName = Utils::clean($baseName);
        $tableName = Utils::clean($tableName);
        if (file_exists(($table =
            $this->dataPathBases . $baseName . DIRECTORY_SEPARATOR . $tableName . Utils::DYNO_EXTENSION))
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $baseName
     * @param string $tableName
     * @return Table
     */
    public function getTable(string $baseName, string $tableName): Table
    {
        $baseName = Utils::clean($baseName);
        $tableName = Utils::clean($tableName);
        return new Table($this->server, $baseName,
            $tableName, $this->asyncFileWrite
        );
    }

    /**
     * @param string $baseName
     * @return array
     * @throws NoBaseExistException
     */
    public function getAllTablesName(string $baseName): array
    {
        if (!$this->baseNameExist($baseName)) {
            throw new NoBaseExistException();
        }
        $tables = array();
        foreach (glob($this->dataPathBases .
            $baseName . DIRECTORY_SEPARATOR . "*" . Utils::DYNO_EXTENSION) as $dir) {
            array_push($tables, basename($dir, Utils::DYNO_EXTENSION));
        }
        return $tables;
    }
}