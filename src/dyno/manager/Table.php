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

namespace dyno\manager;

use dyno\event\TranslationContainer;
use dyno\exception\{
    BaseException\NoBaseExistException, TableException\InvalidReturnValueException, TableException\InvalidTableException, TableException\NoTableExistException, TableException\RemoveInvalidKeyException
};
use dyno\Server;
use dyno\task\FileWriteTask;

class Table
{
    /** @var Server */
    protected $server;
    /** @var mixed|null */
    protected $tablePathJson;
    /** @var string */
    private $baseName;
    /** @var string */
    private $tableName;
    /** @var string */
    private $tablePath;
    /** @var bool */
    private $asyncWriteFile;

    /**
     * Table constructor.
     * @param Server $server
     * @param string $baseName
     * @param string $tableName
     * @param bool $asyncWriteFile
     */
    public function __construct(Server $server, string $baseName, string $tableName, bool $asyncWriteFile)
    {
        $this->baseName = $baseName;
        $this->tableName = $tableName;
        $this->tablePath = $server->getBases()->getDataPathBases() . $baseName .
            DIRECTORY_SEPARATOR . $tableName . Utils::DYNO_EXTENSION;
        if (file_exists($this->tablePath)) {
            $this->tablePathJson = json_decode(file_get_contents($this->tablePath), true);
        } else {
            $this->tablePathJson = null;
        }
        $this->server = $server;
        $this->asyncWriteFile = $asyncWriteFile;
    }

    /**
     * @param string $key
     * @param string $string
     * @throws InvalidTableException
     */
    public function putString(string $key, string $string)
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            $json[$key] = $string;
            $this->updateTable($json);
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @return bool
     */
    private function isValidTable(): bool
    {
        if ($this->server->getTables()->tableNameExist($this->baseName, $this->tableName)) {
            if (Utils::json_validator(file_get_contents($this->tablePath))) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $ar
     */
    protected function updateTable(array $ar)
    {
        if ($this->asyncWriteFile == true) {
            $this->server->getScheduler()->scheduleAsyncTask(
                new FileWriteTask($this->tablePath, json_encode($ar))
            );
        } else {
            file_put_contents($this->tablePath, json_encode($ar, JSON_PRETTY_PRINT));
        }
    }

    /**
     * @param string $key
     * @return null|string
     * @throws InvalidReturnValueException
     * @throws InvalidTableException
     */
    public function getString(string $key): ?string
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            if (isset($json[$key])) {
                if ($this->server->hasStrictMode()) {
                    if (!is_string($json[$key])) {
                        throw new InvalidReturnValueException("getString|" . gettype($json[$key]));
                    }
                }
                return (string)$json[$key];
            } else {
                return null;
            }
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @param int $integer
     * @throws InvalidTableException
     */
    public function putInt(string $key, int $integer)
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            $json[$key] = $integer;
            $this->updateTable($json);
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @return int|null
     * @throws InvalidReturnValueException
     * @throws InvalidTableException
     */
    public function getInt(string $key): ?int
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            if (isset($json[$key])) {
                if ($this->server->hasStrictMode()) {
                    if (!is_int($json[$key])) {
                        throw new InvalidReturnValueException("getInt|" . gettype($json[$key]));
                    }
                }
                return (int)$json[$key];
            } else {
                return null;
            }
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @param float $float
     * @throws InvalidTableException
     */
    public function putFloat(string $key, float $float)
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            $json[$key] = $float;
            $this->updateTable($json);
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @return float|null
     * @throws InvalidReturnValueException
     * @throws InvalidTableException
     */
    public function getFloat(string $key): ?float
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            if (isset($json[$key])) {
                if ($this->server->hasStrictMode()) {
                    if (!is_float($json[$key])) {
                        throw new InvalidReturnValueException("getFloat|" . gettype($json[$key]));
                    }
                }
                return (float)$json[$key];
            } else {
                return null;
            }
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @param array $ar
     * @throws InvalidTableException
     */
    public function putArray(string $key, array $ar)
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            $json[$key] = $ar;
            $this->updateTable($json);
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @return array|null
     * @throws InvalidReturnValueException
     * @throws InvalidTableException
     */
    public function getArray(string $key): ?array
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            if (isset($json[$key])) {
                if ($this->server->hasStrictMode()) {
                    if (!is_array($json[$key])) {
                        throw new InvalidReturnValueException("getArray|" . gettype($json[$key]));
                    }
                }
                return (array)$json[$key];
            } else {
                return null;
            }
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @param bool $boolean
     * @throws InvalidTableException
     */
    public function putBool(string $key, bool $boolean)
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            $json[$key] = $boolean;
            $this->updateTable($json);
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @return bool|null
     * @throws InvalidReturnValueException
     * @throws InvalidTableException
     */
    public function getBool(string $key): ?bool
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            if (isset($json[$key])) {
                if ($this->server->hasStrictMode()) {
                    if (!is_bool($json[$key])) {
                        throw new InvalidReturnValueException("getBool|" . gettype($json[$key]));
                    }
                }
                return (bool)$json[$key];
            } else {
                return null;
            }
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @return array
     * @throws InvalidTableException
     */
    public function getAllKeyValue(): array
    {
        if ($this->isValidTable()) {
            return $this->tablePathJson;
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @throws InvalidTableException
     * @throws RemoveInvalidKeyException
     */
    public function removeKey(string $key)
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            if (isset($json[$key])) {
                unset($json[$key]);
                $this->updateTable($json);
            } else {
                throw new RemoveInvalidKeyException($this->server->getLanguage()->translate(
                    new TranslationContainer("%dyno.exception.table.removeKeyException", [
                        $key
                    ])
                ));
            }
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @return bool
     * @throws InvalidTableException
     */
    public function keyExist(string $key): bool
    {
        if ($this->isValidTable()) {
            $json = $this->tablePathJson;
            if (isset($json[$key])) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @param string $key
     * @return ValueMath
     */
    public function valueMath(string $key): ValueMath
    {
        return new ValueMath($this->server, $this->baseName,
            $this->tableName, $key, $this->asyncWriteFile);
    }

    /**
     * @throws InvalidTableException
     * @throws NoBaseExistException
     * @throws NoTableExistException
     */
    public function remove()
    {
        if ($this->isValidTable()) {
            $this->server->getTables()->removeTable($this->baseName, $this->tableName);
        } else {
            throw new InvalidTableException();
        }
    }

    /**
     * @throws InvalidTableException
     */
    public function reset()
    {
        if ($this->isValidTable()) {
            $this->updateTable([]);
        } else {
            throw new InvalidTableException();
        }
    }
}