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
use dyno\exception\BaseException\{
    BaseAlreadyExistException, NoBaseExistException
};
use dyno\Server;

class Bases
{
    /** @var Server */
    protected $server;
    /** @var string */
    protected $dataPathBases;

    /**
     * Bases constructor.
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->dataPathBases = $server->getDataPath() .
            DIRECTORY_SEPARATOR . "bases" . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $baseName
     * @throws BaseAlreadyExistException
     */
    public function createBase(string $baseName)
    {
        $baseName = Utils::clean($baseName);
        if (file_exists(($base = $this->dataPathBases . $baseName))) {
            throw new BaseAlreadyExistException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.base.BaseAlreadyExistException", [
                    $baseName
                ])
            ));
        }
        @mkdir($base);
    }

    /**
     * @param string $baseName
     * @throws NoBaseExistException
     */
    public function removeBase(string $baseName)
    {
        $baseName = Utils::clean($baseName);
        if (!file_exists(($base = $this->dataPathBases . $baseName))) {
            throw new NoBaseExistException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.base.NoBaseExistException", [
                    $baseName
                ])
            ));
        }
        Utils::deleteDir($base);
    }

    /**
     * @param string $baseName
     * @return bool
     */
    public function baseNameExist(string $baseName): bool
    {
        $baseName = Utils::clean($baseName);
        if (file_exists($this->dataPathBases . $baseName)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getAllBasesName(): array
    {
        $bases = array();
        foreach (glob($this->dataPathBases . "*", GLOB_ONLYDIR) as $dir) {
            array_push($bases, basename($dir));
        }
        return $bases;
    }

    /**
     * @return string
     */
    public function getDataPathBases(): string
    {
        return $this->dataPathBases;
    }
}