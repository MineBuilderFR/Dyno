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


namespace dyno\command\defaults;

use dyno\command\CommandSender;
use dyno\event\TranslationContainer;
use dyno\network\packages\Info;
use dyno\plugin\Plugin;
use dyno\utils\TextFormat;

class VersionCommand extends VanillaCommand
{

    /**
     * VersionCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "%dyno.command.version.description",
            "%dyno.command.version.usage",
            ["ver", "about"]
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
        if (\count($args) === 0) {
            $sender->sendMessage(new TranslationContainer("dyno.server.info.extended", [
                $sender->getServer()->getName(),
                $sender->getServer()->getDynoVersion(),
                $sender->getServer()->getCodename(),
                $sender->getServer()->getVersion(),
                Info::CURRENT_PROTOCOL,
            ]));
        } else {
            $pluginName = \implode(" ", $args);
            $exactPlugin = $sender->getServer()->getPluginManager()->getPlugin($pluginName);

            if ($exactPlugin instanceof Plugin) {
                $this->describeToSender($exactPlugin, $sender);

                return \true;
            }

            $found = \false;
            $pluginName = \strtolower($pluginName);
            foreach ($sender->getServer()->getPluginManager()->getPlugins() as $plugin) {
                if (\stripos($plugin->getName(), $pluginName) !== \false) {
                    $this->describeToSender($plugin, $sender);
                    $found = \true;
                }
            }

            if (!$found) {
                $sender->sendMessage(new TranslationContainer("dyno.command.version.noSuchPlugin"));
            }
        }

        return true;
    }

    /**
     * @param Plugin $plugin
     * @param CommandSender $sender
     */
    private function describeToSender(Plugin $plugin, CommandSender $sender)
    {
        $desc = $plugin->getDescription();
        $sender->sendMessage(TextFormat::DARK_GREEN . $desc->getName() . TextFormat::WHITE . " version " . TextFormat::DARK_GREEN . $desc->getVersion());

        if ($desc->getDescription() != \null) {
            $sender->sendMessage($desc->getDescription());
        }

        if ($desc->getWebsite() != \null) {
            $sender->sendMessage("Website: " . $desc->getWebsite());
        }

        if (count($authors = $desc->getAuthors()) > 0) {
            if (count($authors) === 1) {
                $sender->sendMessage("Author: " . implode(", ", $authors));
            } else {
                $sender->sendMessage("Authors: " . implode(", ", $authors));
            }
        }
    }
}