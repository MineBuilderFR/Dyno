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

use dyno\event\TranslationContainer;
use dyno\plugin\Plugin;

class PluginCommand extends Command implements PluginIdentifiableCommand
{

    /** @var Plugin */
    private $owningPlugin;
    /** @var CommandExecutor */
    private $executor;

    /**
     * @param string $name
     * @param Plugin $owner
     */
    public function __construct(string $name, Plugin $owner)
    {
        parent::__construct($name);
        $this->owningPlugin = $owner;
        $this->executor = $owner;
        $this->usageMessage = "";
    }


    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool|mixed
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$this->owningPlugin->isEnabled()) {
            return false;
        }
        $success = $this->executor->onCommand($sender, $this, $commandLabel, $args);
        if (!$success and $this->usageMessage !== "") {
            $sender->sendMessage(new TranslationContainer("dyno.commands.generic.usage", [$this->usageMessage]));
        }
        return $success;
    }

    /**
     * @return CommandExecutor
     */
    public function getExecutor(): CommandExecutor
    {
        return $this->executor;
    }

    /**
     * @param CommandExecutor $executor
     */
    public function setExecutor(CommandExecutor $executor)
    {
        $this->executor = ($executor != null) ? $executor : $this->owningPlugin;
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin
    {
        return $this->owningPlugin;
    }
}
