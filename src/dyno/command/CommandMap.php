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


interface CommandMap
{

    /**
     * @param string $fallbackPrefix
     * @param Command[] $commands
     */
    public function registerAll(string $fallbackPrefix, array $commands);

    /**
     * @param string $fallbackPrefix
     * @param Command $command
     * @param string $label
     */
    public function register(string $fallbackPrefix, Command $command, ?string $label = null);

    /**
     * @param CommandSender $sender
     * @param string $cmdLine
     * @return boolean
     */
    public function dispatch(CommandSender $sender, string $cmdLine);

    /**
     * @return void
     */
    public function clearCommands(): void;

    /**
     * @param string $name
     * @return Command
     */
    public function getCommand($name);

}