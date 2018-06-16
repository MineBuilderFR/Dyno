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

namespace dyno\command;

use dyno\event\TextContainer;

class RemoteConsoleCommandSender extends ConsoleCommandSender
{

    /** @var string */
    private $messages = "";

    /**
     * @param string $message
     */
    public function sendMessage($message)
    {
        if ($message instanceof TextContainer) {
            $message = $this->getServer()->getLanguage()->translate($message);
        } else {
            $message = $this->getServer()->getLanguage()->translateString($message);
        }

        $this->messages .= trim($message, "\r\n") . "\n";
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->messages;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "Rcon";
    }
}