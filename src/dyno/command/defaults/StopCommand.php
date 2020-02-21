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

namespace dyno\command\defaults;

use dyno\command\CommandSender;
use dyno\event\TranslationContainer;

class StopCommand extends VanillaCommand
{

    /**
     * StopCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "%dyno.command.stop.description",
            "%dyno.commands.stop.usage"
        );
    }

    /**
     * @param CommandSender $sender
     * @param string $currentAlias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $currentAlias, array $args): bool
    {
        $sender->getServer()->displayTranslation(new TranslationContainer("dyno.commands.stop.start"));
        $sender->getServer()->shutdown();
        return true;
    }
}