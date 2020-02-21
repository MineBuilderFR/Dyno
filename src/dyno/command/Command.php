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

abstract class Command
{
    /** @var string */
    protected $description = "";
    /** @var string */
    protected $usageMessage;
    /** @var string */
    private $name;
    /** @var string */
    private $nextLabel;
    /** @var string */
    private $label;
    /** @var array */
    private $aliases = [];
    /** @var array */
    private $activeAliases = [];
    /** @var CommandMap */
    private $commandMap = null;

    /**
     * @param string $name
     * @param string $description
     * @param string $usageMessage
     * @param string[] $aliases
     */
    public function __construct(string $name, string $description = "",
                                ?string $usageMessage = null, array $aliases = [])
    {
        $this->name = $name;
        $this->nextLabel = $name;
        $this->label = $name;
        $this->description = $description;
        $this->usageMessage = $usageMessage === null ? "/" . $name : $usageMessage;
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return bool
     */
    public abstract function execute(CommandSender $sender, string $commandLabel, array $args): bool;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function setLabel(string $name)
    {
        $this->nextLabel = $name;
        if (!$this->isRegistered()) {
            $this->label = $name;

            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isRegistered(): bool
    {
        return $this->commandMap !== null;
    }

    /**
     * Registers the command into a Command map
     * @param CommandMap $commandMap
     * @return bool
     */
    public function register(CommandMap $commandMap): bool
    {
        if ($this->allowChangesFrom($commandMap)) {
            $this->commandMap = $commandMap;

            return true;
        }

        return false;
    }

    /**
     * @param CommandMap $commandMap
     * @return bool
     */
    private function allowChangesFrom(CommandMap $commandMap): bool
    {
        return $this->commandMap === null or $this->commandMap === $commandMap;
    }

    /**
     * @param CommandMap $commandMap
     *
     * @return bool
     */
    public function unregister(CommandMap $commandMap): bool
    {
        if ($this->allowChangesFrom($commandMap)) {
            $this->commandMap = null;
            $this->activeAliases = $this->aliases;
            $this->label = $this->nextLabel;

            return true;
        }

        return false;
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

    /**
     * @return string
     */
    public function getUsage(): string
    {
        return $this->usageMessage;
    }

    /**
     * @param string $usage
     */
    public function setUsage(string $usage)
    {
        $this->usageMessage = $usage;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
