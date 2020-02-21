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

namespace dyno;

use dyno\event\{
    client\ClientAuthEvent, client\ClientConnectEvent, client\ClientDisconnectEvent, client\ClientRecvPacketEvent, client\ClientSendPacketEvent, TranslationContainer
};
use dyno\exception\{
    PacketException\OutputReceivedException, TableException\InvalidTableException, TableException\math\InvalidCalculException
};
use dyno\manager\Utils;
use dyno\network\dynonet\DynoInterface;
use dyno\network\packages\{
    ConnectPacket, DataPacket, DisconnectPacket, executor\inputPacket, executor\outputPacket, HeartbeatPacket, Info, InformationPacket
};
use dyno\utils\TextFormat;
use dynoLibPacket\{
    inputPacketLib, libInterface\MathInterface, libOptions\BaseOptionsInterface, libOptions\KeyValueOptionsInterface, libOptions\TableOptionsInterface
};

class Client
{
    /** @var Server */
    private $server;
    /** @var DynoInterface */
    private $interface;
    /** @var string */
    private $ip;
    /** @var int */
    private $port;
    /** @var bool */
    private $verified = false;
    /** @var mixed */
    private $lastUpdate;
    /** @var string */
    private $description;
    /** @var float */
    private $tps = 20;
    /** @var float */
    private $load = 0.0;
    /** @var float */
    private $upTime = 0.0;

    /**
     * Client constructor.
     * @param DynoInterface $interface
     * @param string $ip
     * @param int $port
     */
    public function __construct(DynoInterface $interface, string $ip, int $port)
    {
        $this->server = $interface->getServer();
        $this->interface = $interface;
        $this->ip = $ip;
        $this->port = $port;
        $this->lastUpdate = microtime(true);

        $this->server->getPluginManager()->callEvent(new ClientConnectEvent($this));
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->ip . ':' . $this->port;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    public function onUpdate()
    {
        if ((microtime(true) - $this->lastUpdate) >= 30) {
            $this->close($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.client.timeout")
            ));
        }
    }

    /**
     * @param string $reason
     * @param bool $needPk
     * @param int $type
     */
    public function close(string $reason = "Generic reason", bool $needPk = true, int $type = DisconnectPacket::TYPE_GENERIC)
    {
        $this->server->getPluginManager()->callEvent($ev = new ClientDisconnectEvent($this, $reason, $type));
        $reason = $ev->getReason();
        $this->server->getLogger()->info($this->server->getLanguage()->translate(
            new TranslationContainer("%dyno.client.disconnect", [
                $this->ip, $this->port, $reason
            ])
        ));
        if ($needPk) {
            $pk = new DisconnectPacket();
            $pk->type = $type;
            $pk->message = $reason;
            $this->sendDataPacket($pk);
        }
        $this->interface->removeClient($this);
        $this->server->removeClient($this);
    }

    /**
     * @param DataPacket $pk
     */
    public function sendDataPacket(DataPacket $pk)
    {
        $this->server->getPluginManager()->callEvent($ev = new ClientSendPacketEvent($this, $pk));
        if (!$ev->isCancelled()) {
            $this->interface->putPacket($this, $pk);
        }
    }

    /**
     * @param DataPacket $packet
     * @throws OutputReceivedException
     */
    public function handleDataPacket(DataPacket $packet)
    {
        $this->server->getPluginManager()->callEvent($ev = new ClientRecvPacketEvent($this, $packet));
        if ($ev->isCancelled()) return;
        switch ($packet->pid()) {
            case Info::HEARTBEAT_PACKET:
                $this->logPacket("HeartBeat");
                /** @var HeartbeatPacket $packet */
                if (!$this->isVerified()) {
                    $this->server->getLogger()->error($this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.client.noVerified", [
                            $this->ip, $this->port
                        ])
                    ));
                    return;
                }
                $this->lastUpdate = microtime(true);

                $this->tps = $packet->tps;
                $this->load = $packet->load;
                $this->upTime = $packet->upTime;

                $pk = new InformationPacket();
                $pk->type = InformationPacket::TYPE_CLIENT_DATA;
                $pk->message = $this->server->getClientData();
                $this->sendDataPacket($pk);

                break;
            case Info::CONNECT_PACKET:
                $this->logPacket("Connect");
                /** @var ConnectPacket $packet */
                if ($packet->protocol != Info::CURRENT_PROTOCOL) {
                    $this->close($this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.client.connect.invalidProtocol", [
                            Info::CURRENT_PROTOCOL
                        ])
                    ), true, DisconnectPacket::TYPE_WRONG_PROTOCOL);
                    return;
                }
                $pk = new InformationPacket();
                $pk->type = InformationPacket::TYPE_LOGIN;
                if ($this->server->comparePassword($packet->password)) {
                    $this->setVerified();
                    $pk->message = InformationPacket::INFO_LOGIN_SUCCESS;
                    $this->description = $packet->description;
                    $this->server->addClient($this);
                    $this->server->getLogger()->notice(TextFormat::GREEN . ">-----<");
                    $this->server->getLogger()->notice($this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.client.connect.successfully.connectionMessage", [
                            $this->ip, $this->port
                        ])
                    ));
                    $this->server->getLogger()->notice($this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.client.connect.successfully.description", [
                            $this->description
                        ])
                    ));
                    $this->server->getLogger()->notice(TextFormat::GREEN . ">-----<");
                    $this->server->updateClientData();
                    $this->sendDataPacket($pk);
                } else {
                    $pk->message = InformationPacket::INFO_LOGIN_FAILED;
                    $this->server->getLogger()->emergency($this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.client.connect.wrongPassword", [
                            $this->ip, $this->port
                        ])
                    ));
                    $this->sendDataPacket($pk);
                    $this->close("Auth failed!");
                }
                $this->server->getPluginManager()->callEvent(new ClientAuthEvent($this, $packet->password));
                break;
            case Info::DISCONNECT_PACKET:
                $this->logPacket("Disconnect");
                /** @var DisconnectPacket $packet */
                $this->close($packet->message, false);
                break;
            case info::INPUT_PACKET:
                /** @var inputPacket $packet */
                $this->logPacket("Input");
                $this->receiveInputPacket($packet);
                break;
            case info::OUTPUT_PACKET:
                $this->logPacket("Output");
                /** @var outputPacket $packet */
                throw new OutputReceivedException($this->server->getLanguage()->translate(
                    new TranslationContainer("%dyno.exception.packet.outputReceivedError")
                ));
                break;
            default:
                $this->server->getLogger()->error($this->server->getLanguage()->translate(
                    new TranslationContainer("%dyno.client.sendUnknownPacket", [
                        $this->ip, $this->port, $packet::NETWORK_ID
                    ])
                ));
        }
    }

    /**
     * @param string $packetname
     */
    public function logPacket(string $packetname)
    {
        if ($this->server->hasLogPacketReceived()) {
            $this->server->getLogger()->debug($this->server->getLanguage()->translate(
                new TranslationContainer("%dyno.client.packetReceivedlog", [
                    $packetname, $this->ip, $this->port
                ])
            ));
        }
    }

    /**
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified()
    {
        $this->verified = true;
    }

    /**
     * @param inputPacket $packet
     */
    private function receiveInputPacket(inputPacket $packet)
    {
        $tunnelKey = $packet->tunnelKey;
        $pk = new outputPacket();
        $pk->tunnelKey = $tunnelKey;
        $errors = array();
        $logs = array();
        $getter = array();
        $internalDynoWriteAsyncFile = $packet->internalDynoWriteAsyncFile;
        /** @var array $input */
        foreach (json_decode($packet->input, true) as $input) {
            /**
             * @var string $type
             * @var array $ar
             */
            foreach ($input as $type => $ar) {
                $f = $this->switchInput($type, $ar, $errors,
                    $logs, $getter, $internalDynoWriteAsyncFile);
                $logs = $f["logs"];
                $getter = $f["getter"];
                $errors = $f["errors"];
            }
        }
        $pk->logs = $logs;
        $pk->errors = $errors;
        $pk->getters = $getter;
        $pk->getterType = $packet->getterType;
        $pk->countErrors = count($errors);
        $pk->pluginClass = $packet->pluginClass;
        $pk->baseInput = $packet->input;
        $pk->want = $packet->want;
        $this->sendDataPacket($pk);
    }

    /**
     * @param string $type
     * @param array $ar
     * @param array $errors
     * @param array $logs
     * @param array $getter
     * @param bool $internalDynoWriteAsyncFile
     * @return array
     */
    private function switchInput(
        string $type, array $ar, array $errors,
        array $logs, array $getter, bool $internalDynoWriteAsyncFile): array
    {
        $errorPusherFunc = function (\Exception $e) use (&$errors, &$logs) {
            array_push($errors, $e->__toString());
            array_push($logs,
                empty($e->getMessage()) ? $e->__toString() : $e->getMessage()
            );
        };
        switch ($type) {
            case inputPacketLib::TYPE_BASE_EXIST:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $identifiable */
                    $identifiable = $ar["identifiable"];
                    $getter[$identifiable] = ($val = $this->server->getBases()->baseNameExist($baseName));
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            $val, gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_TABLE_EXIST:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $identifiable */
                    $identifiable = $ar["identifiable"];
                    $getter[$identifiable] = ($val = $this->server->getTables()->tableNameExist($baseName, $tableName));
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            $val, gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_KEY_EXIST:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var string $identifiable */
                    $identifiable = $ar["identifiable"];
                    $getter[$identifiable] = ($val = $this->server->getTables()->getTable($baseName, $tableName)->keyExist($key));
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            $val, gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_CREATE_BASE:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->baseOptionsManager($baseName, $options) === false) break;
                    $this->server->getBases()->createBase($baseName);
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.createBaseSuccessfully", [
                            Utils::clean($baseName)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_REMOVE_BASE:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->baseOptionsManager($baseName, $options) === false) break;
                    $this->server->getBases()->removeBase($baseName);
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.removeBaseSuccessfully", [
                            $baseName
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_CREATE_TABLE:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->tableOptionsManager($baseName, $tableName, $options) == false) break;
                    $this->server->getTables()
                        ->setAsyncFileWrite($internalDynoWriteAsyncFile)
                        ->createTable($baseName, $tableName);
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.createTableSuccessfully", [
                            $tableName, $baseName
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_REMOVE_TABLE:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->tableOptionsManager($baseName, $tableName, $options) == false) break;
                    $this->server->getTables()->removeTable($baseName, $tableName);
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.removeTableSuccessfully", [
                            $tableName, $baseName
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_RESET_TABLE:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->tableOptionsManager($baseName, $tableName, $options) == false) break;
                    $this->server->getTables()
                        ->setAsyncFileWrite($internalDynoWriteAsyncFile)
                        ->getTable($baseName, $tableName)
                        ->reset();
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.resetTableSuccessfully", [
                            $tableName, $baseName
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_GET_STRING:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var string $identifiable */
                    $identifiable = $ar["identifiable"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $getter[$identifiable] = ($val = $this->server->getTables()->getTable($baseName, $tableName)->getString($key));
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            $val, gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_GET_INT:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var string $identifiable */
                    $identifiable = $ar["identifiable"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $getter[$identifiable] = ($val = $this->server->getTables()->getTable($baseName, $tableName)->getInt($key));
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            $val, gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_GET_FLOAT:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var string $identifiable */
                    $identifiable = $ar["identifiable"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $getter[$identifiable] = ($val = $this->server->getTables()->getTable($baseName, $tableName)->getFloat($key));
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            $val, gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_GET_BOOL:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var string $identifiable */
                    $identifiable = $ar["identifiable"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $getter[$identifiable] = ($val = $this->server->getTables()->getTable($baseName, $tableName)->getBool($key));
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            $val, gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_GET_ARRAY:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var string $identifiable */
                    $identifiable = $ar["identifiable"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $getter[$identifiable] = ($val = $this->server->getTables()->getTable($baseName, $tableName)->getArray($key));
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            json_encode($val), gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_GET_ALL_BASE:
                try {
                    /** @var string $identifiable */
                    $identifiable = $ar["identifiable"];
                    $getter[$identifiable] = ($val = $this->server->getBases()->getAllBasesName());
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            json_encode($val), gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_GET_ALL_TABLE:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $identifiable */
                    $identifiable = $ar["identifiable"];
                    $getter[$identifiable] = ($val = $this->server->getTables()->getAllTablesName($baseName));
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            json_encode($val), gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_GET_ALL_KEY_VALUE:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var  $identifiable */
                    $identifiable = $ar["identifiable"];
                    $getter[$identifiable] = ($val = $this->server->getTables()->getTable($baseName, $tableName)->getAllKeyValue());
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.getSuccessfully", [
                            json_encode($val), gettype($val)
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_PUT_STRING:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var string $value */
                    $value = $ar["value"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $this->server->getTables()
                        ->setAsyncFileWrite($internalDynoWriteAsyncFile)
                        ->getTable($baseName, $tableName)
                        ->putString($key, $value);
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.putSuccessfully", [
                            $key, $value, gettype($value), $tableName, $baseName
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_PUT_INT:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var int $value */
                    $value = $ar["value"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $this->server->getTables()
                        ->setAsyncFileWrite($internalDynoWriteAsyncFile)
                        ->getTable($baseName, $tableName)
                        ->putInt($key, $value);
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.putSuccessfully", [
                            $key, $value, gettype($value), $tableName, $baseName
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_PUT_FLOAT:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var float $value */
                    $value = $ar["value"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $this->server->getTables()
                        ->setAsyncFileWrite($internalDynoWriteAsyncFile)
                        ->getTable($baseName, $tableName)
                        ->putFloat($key, $value);
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.putSuccessfully", [
                            $key, $value, gettype($value), $tableName, $baseName
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_PUT_BOOL:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var bool $value */
                    $value = $ar["value"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $this->server->getTables()
                        ->setAsyncFileWrite($internalDynoWriteAsyncFile)
                        ->getTable($baseName, $tableName)
                        ->putBool($key, $value);
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.putSuccessfully", [
                            $key, $value, gettype($value), $tableName, $baseName
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }

                break;
            case inputPacketLib::TYPE_PUT_ARRAY:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var array $value */
                    $value = $ar["value"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $this->server->getTables()
                        ->setAsyncFileWrite($internalDynoWriteAsyncFile)
                        ->getTable($baseName, $tableName)
                        ->putArray($key, $value);
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.putSuccessfully", [
                            $key, json_encode($value), gettype($value), $tableName, $baseName
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_REMOVE_KEY:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var int[] $options */
                    $options = $ar["options"];
                    if ($this->keyValueOptionsManager($baseName, $tableName, $key, $options) == false) break;
                    $this->server->getTables()
                        ->setAsyncFileWrite($internalDynoWriteAsyncFile)
                        ->getTable($baseName, $tableName)
                        ->removeKey($key);
                    array_push($logs, $this->server->getLanguage()->translate(
                        new TranslationContainer("%dyno.packet.received.inputPacket.removeKeySuccessfully", [
                            $key, $tableName, $baseName
                        ])
                    ));
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                }
                break;
            case inputPacketLib::TYPE_VALUE_MATH:
                try {
                    /** @var string $baseName */
                    $baseName = $ar["baseName"];
                    /** @var string $tableName */
                    $tableName = $ar["tableName"];
                    /** @var string $key */
                    $key = $ar["key"];
                    /** @var string $math */
                    $math = $ar["math"];
                    $valueMath = $this->server->getTables()
                        ->setAsyncFileWrite($internalDynoWriteAsyncFile)
                        ->getTable($baseName, $tableName)
                        ->valueMath($key);
                    switch ($math) {
                        case MathInterface::ADDITION:
                            $x = $ar["x"];
                            $valueMath->addition($x);
                            break;
                        case MathInterface::SUBTRACTION:
                            $x = $ar["x"];
                            $valueMath->subtract($x);
                            break;
                        case MathInterface::MULTIPLICATION:
                            $x = $ar["x"];
                            $valueMath->multiplication($x);
                            break;
                        case MathInterface::DIVIDE:
                            $x = $ar["x"];
                            $valueMath->divide($x);
                            break;
                        default:
                            throw new InvalidCalculException($this->server->getLanguage()->translate(
                                new TranslationContainer("%dyno.exception.table.math.InvalidCalculException")
                            ));
                            break;
                    }
                } catch (\Exception $e) {
                    $errorPusherFunc($e);
                } catch (\Throwable $e) { //Divide By Zero
                    $errorPusherFunc($e);
                }
                break;
        }
        $final = [
            "logs" => $logs,
            "errorPusherFunc" => $errorPusherFunc,
            "getter" => $getter,
            "errors" => $errors
        ];
        return $final;
    }

    /**
     * @param string $baseName
     * @param int[] $options
     * @return bool
     */
    private function baseOptionsManager(string $baseName, array $options): bool
    {
        if (count($options) > 0) {
            /** @var int $option */
            foreach ($options as $option) {
                if ($option === BaseOptionsInterface::ONLY_IF_BASE_EXIST) {
                    if (!$this->server->getBases()->baseNameExist($baseName)) {
                        return false;
                    }
                }
                if ($option === BaseOptionsInterface::ONLY_IF_BASE_NOT_EXIST) {
                    if ($this->server->getBases()->baseNameExist($baseName)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @param string $baseName
     * @param string $tableName
     * @param int[] $options
     * @return bool
     */
    private function tableOptionsManager(string $baseName, string $tableName, array $options): bool
    {
        if (count($options) > 0) {
            if ($this->baseOptionsManager($baseName, $options) === false) return false;
            /** @var int $option */
            foreach ($options as $option) {
                if ($option === TableOptionsInterface::ONLY_IF_TABLE_EXIST) {
                    if (!$this->server->getTables()->tableNameExist($baseName, $tableName)) {
                        return false;
                    }
                }
                if ($option === TableOptionsInterface::ONLY_IF_TABLE_NOT_EXIST) {
                    if ($this->server->getTables()->tableNameExist($baseName, $tableName)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @param string $baseName
     * @param string $tableName
     * @param string $key
     * @param array $options
     * @return bool
     * @throws InvalidTableException
     */
    private function keyValueOptionsManager(string $baseName, string $tableName, string $key, array $options): bool
    {
        if (count($options) > 0) {
            if ($this->tableOptionsManager($baseName, $tableName, $options) == false) return false;
            /** @var int $option */
            foreach ($options as $option) {
                if ($option === KeyValueOptionsInterface::ONLY_IF_KEY_EXIST) {
                    if (!$this->server->getTables()->getTable($baseName, $tableName)->keyExist($key)) {
                        return false;
                    }
                }
                if ($option === KeyValueOptionsInterface::ONLY_IF_KEY_NOT_EXIST) {
                    if ($this->server->getTables()->getTable($baseName, $tableName)->keyExist($key)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return float
     */
    public function getTps(): float
    {
        return $this->tps;
    }

    /**
     * @return float
     */
    public function getLoad(): float
    {
        return $this->load;
    }

    /**
     * @return float
     */
    public function getUpTime(): float
    {
        return $this->upTime;
    }
}
