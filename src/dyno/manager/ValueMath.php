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
use dyno\exception\TableException\{
    InvalidTableException, KeyDoesNotExistException, math\InvalidValueMathException
};
use dyno\Server;

class ValueMath extends Table
{
    /** @var string */
    private $key;

    /**
     * ValueMath constructor.
     * @param Server $server
     * @param string $baseName
     * @param string $tableName
     * @param string $key
     * @param bool $asyncWriteFile
     */
    public function __construct(Server $server, string $baseName,
                                string $tableName, string $key, bool $asyncWriteFile)
    {
        parent::__construct($server, $baseName, $tableName, $asyncWriteFile);
        $this->key = $key;
    }

    /**
     * @param $x float|integer
     * @throws InvalidValueMathException
     * @throws KeyDoesNotExistException
     * @throws InvalidTableException
     */
    public function multiplication($x)
    {
        if (!is_float($x) and !is_int($x)) {
            throw new InvalidValueMathException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.table.math.InvalidValueMathException.notValidValue", [
                        $x
                    ]
                )));
        }
        if ($this->keyExist($this->key)) {
            $json = $this->tablePathJson;
            if (!$this->isValideValueCalcul($json, $this->key)) {
                throw new InvalidValueMathException($this->server->getLanguage()->translate(
                    new TranslationContainer("%dyno.exception.table.math.InvalidValueMathException.notValidValue", [
                            $json[$this->key]
                        ]
                    )));
            }
            settype($json[$this->key], gettype($x));
            $json[$this->key] *= $x;
            $this->updateTable($json);
        } else {
            throw new KeyDoesNotExistException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.table.math.KeyDoesNotExistException")
            ));
        }
    }

    private function isValideValueCalcul($json, $value)
    {
        if (is_float($json[$value]) or is_int($json[$value])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $x float|integer
     * @throws InvalidValueMathException
     * @throws KeyDoesNotExistException
     * @throws InvalidTableException
     */
    public function divide($x)
    {
        if (!is_float($x) and !is_int($x)) {
            throw new InvalidValueMathException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.table.math.InvalidValueMathException.notValidValue", [
                        $x
                    ]
                )));
        }
        if ($this->keyExist($this->key)) {
            $json = $this->tablePathJson;
            if (!$this->isValideValueCalcul($json, $this->key)) {
                throw new InvalidValueMathException($this->server->getLanguage()->translate(
                    new TranslationContainer("%dyno.exception.table.math.InvalidValueMathException.notValidValue", [
                            $json[$this->key]
                        ]
                    )));
            }
            settype($json[$this->key], gettype($x));
            $json[$this->key] /= $x;
            $this->updateTable($json);
        } else {
            throw new KeyDoesNotExistException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.table.math.KeyDoesNotExistException")
            ));
        }
    }

    /**
     * @param $x float|integer
     * @throws InvalidValueMathException
     * @throws KeyDoesNotExistException
     * @throws InvalidTableException
     */
    public function subtract($x)
    {
        if (!is_float($x) and !is_int($x)) {
            throw new InvalidValueMathException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.table.math.InvalidValueMathException.notValidValue", [
                        $x
                    ]
                )));
        }
        if ($this->keyExist($this->key)) {
            $json = $this->tablePathJson;
            if (!$this->isValideValueCalcul($json, $this->key)) {
                throw new InvalidValueMathException($this->server->getLanguage()->translate(
                    new TranslationContainer("%dyno.exception.table.math.InvalidValueMathException.notValidValue", [
                            $json[$this->key]
                        ]
                    )));
            }
            settype($json[$this->key], gettype($x));
            $json[$this->key] -= $x;
            $this->updateTable($json);
        } else {
            throw new KeyDoesNotExistException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.table.math.KeyDoesNotExistException")
            ));
        }
    }

    /**
     * @param $x float|integer
     * @throws InvalidValueMathException
     * @throws KeyDoesNotExistException
     * @throws InvalidTableException
     */
    public function addition($x)
    {
        if (!is_float($x) and !is_int($x)) {
            throw new InvalidValueMathException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.table.math.InvalidValueMathException.notValidValue", [
                        $x
                    ]
                )));
        }
        if ($this->keyExist($this->key)) {
            $json = $this->tablePathJson;
            if (!$this->isValideValueCalcul($json, $this->key)) {
                throw new InvalidValueMathException($this->server->getLanguage()->translate(
                    new TranslationContainer("%dyno.exception.table.math.InvalidValueMathException.notValidValue", [
                            $json[$this->key]
                        ]
                    )));
            }
            settype($json[$this->key], gettype($x));
            $json[$this->key] += $x;
            $this->updateTable($json);
        } else {
            throw new KeyDoesNotExistException($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.table.math.KeyDoesNotExistException")
            ));
        }
    }
}