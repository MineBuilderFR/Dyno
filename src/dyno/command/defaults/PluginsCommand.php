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
use dyno\utils\TextFormat;

class PluginsCommand extends VanillaCommand
{

    /**
     * PluginsCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "%dyno.command.plugins.description",
            "%dyno.command.plugins.usage",
            ["pl"]
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
        $this->sendPluginList($sender);
        return true;
    }

    /**
     * @param CommandSender $sender
     */
    private function sendPluginList(CommandSender $sender)
    {
        $list = "";
        foreach (($plugins = $sender->getServer()->getPluginManager()->getPlugins()) as $plugin) {
            if (strlen($list) > 0) {
                $list .= TextFormat::WHITE . ", ";
            }
            $list .= $plugin->isEnabled() ? TextFormat::GREEN : TextFormat::RED;
            $list .= $plugin->getDescription()->getFullName();
        }

        $sender->sendMessage(new TranslationContainer("dyno.command.plugins.success", [count($plugins), $list]));
    }
}
