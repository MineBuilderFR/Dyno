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

use dyno\command\defaults\{
    Base\BaseCommand, Base\TableCommand, ExtractPluginCommand, GarbageCollectorCommand, HelpCommand, MakePluginCommand, MakeServerCommand, PluginsCommand, StatusCommand, StopCommand, VanillaCommand, VersionCommand
};
use dyno\event\TranslationContainer;
use dyno\Server;
use dyno\utils\{
    MainLogger, TextFormat
};

class SimpleCommandMap implements CommandMap
{

    /** @var array */
    protected $knownCommands = [];
    /** @var Server */
    private $server;

    /**
     * SimpleCommandMap constructor.
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->setDefaultCommands();
    }

    private function setDefaultCommands()
    {
        $this->registerAll("dyno", [
            new ExtractPluginCommand("ep"),
            new GarbageCollectorCommand("gc"),
            new HelpCommand("help"),
            new MakePluginCommand("mp"),
            new MakeServerCommand("ms"),
            new PluginsCommand("plugins"),
            new StatusCommand("status"),
            new StopCommand("stop"),
            new VersionCommand("version"),
            new BaseCommand("base"),
            new TableCommand("table")
        ]);
    }

    /**
     * @param string $fallbackPrefix
     * @param array $commands
     */
    public function registerAll(string $fallbackPrefix, array $commands)
    {
        foreach ($commands as $command) {
            $this->register($fallbackPrefix, $command);
        }
    }

    /**
     * @param string $fallbackPrefix
     * @param Command $command
     * @param null|string $label
     * @return bool
     */
    public function register(string $fallbackPrefix, Command $command, ?string $label = null)
    {
        if ($label === null) {
            $label = $command->getName();
        }
        $label = strtolower(trim($label));
        $fallbackPrefix = strtolower(trim($fallbackPrefix));

        $registered = $this->registerAlias($command, false, $fallbackPrefix, $label);

        if (!$registered) {
            $command->setLabel($fallbackPrefix . ":" . $label);
        }

        $command->register($this);

        return $registered;
    }

    /**
     * @param Command $command
     * @param bool $isAlias
     * @param string $fallbackPrefix
     * @param string $label
     * @return bool
     */
    private function registerAlias(Command $command, bool $isAlias, string $fallbackPrefix, string $label): bool
    {
        $this->knownCommands[$fallbackPrefix . ":" . $label] = $command;
        if (($command instanceof VanillaCommand or $isAlias) and isset($this->knownCommands[$label])) {
            return false;
        }
        if (isset($this->knownCommands[$label]) and $this->knownCommands[$label]->getLabel() !== null and $this->knownCommands[$label]->getLabel() === $label) {
            return false;
        }
        if (!$isAlias) {
            $command->setLabel($label);
        }
        $this->knownCommands[$label] = $command;
        return true;
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLine
     * @return bool
     */
    public function dispatch(CommandSender $sender, string $commandLine): bool
    {
        $args = explode(" ", $commandLine);
        if (count($args) === 0) {
            return false;
        }
        $sentCommandLabel = strtolower(array_shift($args));
        $target = $this->getCommand($sentCommandLabel);
        if ($target === null) {
            return false;
        }

        try {
            $target->execute($sender, $sentCommandLabel, $args);
        } catch (\Throwable $e) {
            $sender->sendMessage(new TranslationContainer(TextFormat::RED . "%dyno.commands.generic.exception"));
            $this->server->getLogger()->critical($this->server->getLanguage()->translateString("dyno.command.exception", [$commandLine, (string)$target, $e->getMessage()]));
            $logger = $sender->getServer()->getLogger();
            if ($logger instanceof MainLogger) {
                $logger->logException($e);
            }
        }
        return true;
    }

    /**
     * @param string $name
     * @return mixed|null|Command
     */
    public function getCommand($name)
    {
        if (isset($this->knownCommands[$name])) {
            return $this->knownCommands[$name];
        }

        return null;
    }

    public function clearCommands(): void
    {
        foreach ($this->knownCommands as $command) {
            $command->unregister($this);
        }
        $this->knownCommands = [];
        $this->setDefaultCommands();
    }

    /**
     * @return Command[]
     */
    public function getCommands(): array
    {
        return $this->knownCommands;
    }
}
