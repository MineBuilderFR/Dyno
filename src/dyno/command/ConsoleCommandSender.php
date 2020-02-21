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

namespace dyno\command;

use dyno\event\TextContainer;
use dyno\Server;
use dyno\utils\MainLogger;

class ConsoleCommandSender implements CommandSender
{

    public function __construct() { }

    public function sendMessage($message)
    {
        if ($message instanceof TextContainer) {
            $message = $this->getServer()->getLanguage()->translate($message);
        } else {
            $message = $this->getServer()->getLanguage()->translateString($message);
        }
        foreach (explode("\n", trim($message)) as $line) {
            MainLogger::getLogger()->info($line);
        }
    }


    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return Server::getInstance();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "CONSOLE";
    }

    /**
     * @return bool
     */
    public function isOp(): bool
    {
        return true;
    }

    /**
     * @param bool $value
     */
    public function setOp(bool $value) { }

}