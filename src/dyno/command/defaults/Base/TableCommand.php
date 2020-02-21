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

namespace dyno\command\defaults\Base;

use dyno\command\{
    CommandSender, defaults\VanillaCommand
};
use dyno\event\TranslationContainer;
use dyno\exception\{
    BaseException\NoBaseExistException, TableException\InvalidReturnValueException, TableException\InvalidTableException, TableException\NoTableExistException, TableException\RemoveInvalidKeyException, TableException\TableAlreadyExistException
};
use dyno\utils\TextFormat;

class TableCommand extends VanillaCommand
{

    /** @var string[] */
    private $put = array(
        "putstring",
        "putint",
        "putfloat",
        "putbool"
    );
    /** @var string[] */
    private $get = array(
        "getstring",
        "getint",
        "getfloat",
        "getbool"
    );

    /**
     * StopCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "%dyno.commands.table.description",
            "%dyno.commands.table.usage",
            ["tb", "tab"]
        );
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        $server = $sender->getServer();
        if (!isset($args[0]) or strtolower($args[0]) == "help") {
            $this->tableHelp($sender);
            return true;
        } elseif (strtolower($args[0]) == "create") {
            if (!isset($args[1]) or !isset($args[2])) {
                $this->tableHelp($sender);
                return true;
            }
            $baseName = $args[1];
            $tableName = $args[2];
            try {
                $server->getTables()->createTable($baseName, $tableName);
                $sender->sendMessage(new TranslationContainer(
                    TextFormat::GREEN . "%dyno.commands.table.create.success", [$tableName, $baseName]
                ));
            } catch (NoBaseExistException $e) {
                $sender->sendMessage(new TranslationContainer(
                    TextFormat::RED . "%dyno.base.noexist", [$baseName]
                ));
            } catch (TableAlreadyExistException $e) {
                $sender->sendMessage(new TranslationContainer(
                    TextFormat::RED . "%dyno.table.alreadyexist", [$tableName]
                ));
            }
            return true;
        } elseif (strtolower($args[0]) == "remove") {
            if (!isset($args[1]) or !isset($args[2])) {
                $this->tableHelp($sender);
                return true;
            }
            $baseName = $args[1];
            $tableName = $args[2];
            try {
                $server->getTables()->removeTable($baseName, $tableName);
                $sender->sendMessage(new TranslationContainer(
                    TextFormat::GREEN . "%dyno.commands.table.remove.success", [$tableName, $baseName]
                ));
            } catch (NoBaseExistException $e) {
                $sender->sendMessage(new TranslationContainer(
                    TextFormat::RED . "%dyno.base.noexist", [$baseName]
                ));
            } catch (NoTableExistException $e) {
                $sender->sendMessage(new TranslationContainer(
                    TextFormat::RED . "%dyno.table.notableexist", [$tableName]
                ));
            }
            return true;
        } elseif (strtolower($args[0]) == "list") {
            if (!isset($args[1])) {
                $this->tableHelp($sender);
                return true;
            }
            $baseName = $args[1];
            try {
                $sender->sendMessage(new TranslationContainer(TextFormat::AQUA .
                    "%dyno.commands.table.list.header", [
                        $baseName,
                        count(($tables = $server->getTables()->getAllTablesName($baseName)))
                    ]
                ));
                foreach ($tables as $basename) {
                    $sender->sendMessage($basename);
                }
            } catch (NoBaseExistException $e) {
                $sender->sendMessage(new TranslationContainer(
                    TextFormat::RED . "%dyno.base.noexist", [$baseName]
                ));
            }
            return true;
        } elseif (in_array(($put = strtolower($args[0])), $this->put)) {
            if (!isset($args[1]) or !isset($args[2])
                or !isset($args[3]) or !isset($args[4])) {
                $this->tableHelp($sender);
                return true;
            }
            $baseName = $args[1];
            $tableName = $args[2];
            $key = $args[3];
            $value = $args[4];
            $table = $server->getTables()->getTable($baseName, $tableName);
            $type = "";
            if ($put == "putstring") {
                try {
                    $table->putString((string)$key, (string)$value);
                    $type = "string";
                    $value = (string)$value;
                } catch (InvalidTableException $e) {
                    $sender->sendMessage(new TranslationContainer(
                        TextFormat::RED . "%dyno.table.invalid"
                    ));
                    return true;
                }
            }
            if ($put == "putint") {
                try {
                    $table->putInt((string)$key, (int)$value);
                    $type = "int";
                    $value = (int)$value;
                } catch (InvalidTableException $e) {
                    $sender->sendMessage(new TranslationContainer(
                        TextFormat::RED . "%dyno.table.invalid"
                    ));
                    return true;
                }
            }
            if ($put == "putbool") {
                try {
                    $table->putBool((string)$key, (bool)$value);
                    $type = "bool";
                    $value = (bool)$value;
                } catch (InvalidTableException $e) {
                    $sender->sendMessage(new TranslationContainer(
                        TextFormat::RED . "%dyno.table.invalid"
                    ));
                    return true;
                }
            }
            if ($put == "putfloat") {
                try {
                    $table->putString((string)$key, (float)$value);
                    $type = "float";
                    $value = (float)$value;
                } catch (InvalidTableException $e) {
                    $sender->sendMessage(new TranslationContainer(
                        TextFormat::RED . "%dyno.table.invalid"
                    ));
                    return true;
                }
            }
            $sender->sendMessage(new TranslationContainer(
                TextFormat::GREEN . "%dyno.commands.table.put.success", [
                    $key, $value, $type, $tableName, $baseName
                ]
            ));
            return true;
        } elseif (in_array(($get = strtolower($args[0])), $this->get)) {
            if (!isset($args[1]) or !isset($args[2])
                or !isset($args[3])) {
                $this->tableHelp($sender);
                return true;
            }
            $baseName = $args[1];
            $tableName = $args[2];
            $key = $args[3];
            $table = $server->getTables()->getTable($baseName, $tableName);
            if ($get == "getstring") {
                try {
                    $final = $table->getString($key);
                    if (!is_null($final)) {
                        $type = "string";
                        $sender->sendMessage(new TranslationContainer(
                            TextFormat::GREEN . "%dyno.commands.table.get.success", [
                                $final, $type
                            ]
                        ));
                    } else {
                        $sender->sendMessage(new TranslationContainer(
                            TextFormat::GREEN . "%dyno.commands.table.get.success.null"
                        ));
                    }
                } catch (InvalidTableException $e) {
                    $sender->sendMessage(new TranslationContainer(
                        TextFormat::RED . "%dyno.table.invalid"
                    ));
                } catch (InvalidReturnValueException $e) {
                    $sender->sendMessage(TextFormat::RED . $e->getMessage());
                }
                return true;
            }
            if ($get == "getint") {
                try {
                    $final = $table->getInt($key);
                    if (!is_null($final)) {
                        $type = "integer";
                        $sender->sendMessage(new TranslationContainer(
                            TextFormat::GREEN . "%dyno.commands.table.get.success", [
                                $final, $type
                            ]
                        ));
                    } else {
                        $sender->sendMessage(new TranslationContainer(
                            TextFormat::GREEN . "%dyno.commands.table.get.success.null"
                        ));
                    }
                } catch (InvalidTableException $e) {
                    $sender->sendMessage(new TranslationContainer(
                        TextFormat::RED . "%dyno.table.invalid"
                    ));
                } catch (InvalidReturnValueException $e) {
                    $sender->sendMessage(TextFormat::RED . $e->getMessage());
                }
                return true;
            }
            if ($get == "getfloat") {
                try {
                    $final = $table->getFloat($key);
                    if (!is_null($final)) {
                        $type = "float";
                        $sender->sendMessage(new TranslationContainer(
                            TextFormat::GREEN . "%dyno.commands.table.get.success", [
                                $final, $type
                            ]
                        ));
                    } else {
                        $sender->sendMessage(new TranslationContainer(
                            TextFormat::GREEN . "%dyno.commands.table.get.success.null"
                        ));
                    }
                } catch (InvalidTableException $e) {
                    $sender->sendMessage(new TranslationContainer(
                        TextFormat::RED . "%dyno.table.invalid"
                    ));
                } catch (InvalidReturnValueException $e) {
                    $sender->sendMessage(TextFormat::RED . $e->getMessage());
                }
                return true;
            }
            if ($get == "getbool") {
                try {
                    $final = $table->getBool($key);
                    if (!is_null($final)) {
                        $type = "bool";
                        $sender->sendMessage(new TranslationContainer(
                            TextFormat::GREEN . "%dyno.commands.table.get.success", [
                                $final, $type
                            ]
                        ));
                    } else {
                        $sender->sendMessage(new TranslationContainer(
                            TextFormat::GREEN . "%dyno.commands.table.get.success.null"
                        ));
                    }
                } catch (InvalidTableException $e) {
                    $sender->sendMessage(new TranslationContainer(
                        TextFormat::RED . "%dyno.table.invalid"
                    ));
                } catch (InvalidReturnValueException $e) {
                    $sender->sendMessage(TextFormat::RED . $e->getMessage());
                }
                return true;
            }
            return true;
        } elseif (strtolower($args[0]) == "reset") {
            if (!isset($args[1]) or !isset($args[2])) {
                $this->tableHelp($sender);
                return true;
            }
            $baseName = $args[1];
            $tableName = $args[2];
            try {
                $server->getTables()->getTable($baseName, $tableName)->reset();
                $sender->sendMessage(new TranslationContainer(
                    TextFormat::GREEN . "%dyno.commands.table.reset.success", [
                        $tableName
                    ]
                ));
            } catch (InvalidTableException $e) {
                $sender->sendMessage(new TranslationContainer(
                    TextFormat::RED . "%dyno.table.invalid"
                ));
            }
            return true;
        } elseif (strtolower($args[0]) == "removekey") {
            if (!isset($args[1]) or !isset($args[2])
                or !isset($args[3])) {
                $this->tableHelp($sender);
                return true;
            }
            $baseName = $args[1];
            $tableName = $args[2];
            $key = $args[3];
            try {
                $server->getTables()->getTable($baseName, $tableName)->removeKey($key);
                $sender->sendMessage(new TranslationContainer(
                    "%dyno.commands.table.removeKey.success", [
                        $key, $tableName, $baseName
                    ]
                ));
            } catch (InvalidTableException $e) {
                $sender->sendMessage(new TranslationContainer(
                    TextFormat::RED . "%dyno.table.invalid"
                ));
            } catch (RemoveInvalidKeyException $e) {
                $sender->sendMessage(TextFormat::RED . $e->getMessage());
            }
            return true;
        } else {
            $this->tableHelp($sender);
            return true;
        }
    }

    /**
     * @param CommandSender $sender
     */
    public function tableHelp(CommandSender $sender)
    {
        $sender->sendMessage(new TranslationContainer("%dyno.commands.table.header"));
        $sender->sendMessage(new TranslationContainer("%dyno.commands.table.create.usage"));
        $sender->sendMessage(new TranslationContainer("%dyno.commands.table.remove.usage"));
        $sender->sendMessage(new TranslationContainer("%dyno.commands.table.list.usage"));
        $sender->sendMessage(new TranslationContainer("%dyno.commands.table.put.usage"));
        $sender->sendMessage(new TranslationContainer("%dyno.commands.table.get.usage"));
        $sender->sendMessage(new TranslationContainer("%dyno.commands.table.removeKey.usage"));
        $sender->sendMessage(new TranslationContainer("%dyno.commands.table.reset.usage"));
    }
}