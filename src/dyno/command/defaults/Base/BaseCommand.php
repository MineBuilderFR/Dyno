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

namespace dyno\command\defaults\Base;

use dyno\command\{
    CommandSender, defaults\VanillaCommand
};
use dyno\event\TranslationContainer;
use dyno\exception\BaseException\{
    BaseAlreadyExistException, NoBaseExistException
};
use dyno\manager\Utils;
use dyno\utils\TextFormat;

class BaseCommand extends VanillaCommand
{

    /**
     * StopCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "%dyno.commands.base.description",
            "%dyno.commands.base.usage",
            ["bs"]
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
        $server = $sender->getServer();
        if (!isset($args[0]) or strtolower($args[0]) == "help") {
            $this->baseHelp($sender);
            return true;
        } elseif (strtolower($args[0]) == "create") {
            if (!isset($args[1])) {
                $this->baseHelp($sender);
                return true;
            }
            unset($args[0]);
            $base = implode(",", $args);
            try {
                $server->getBases()->createBase($base);
                $sender->sendMessage(new TranslationContainer(TextFormat::GREEN .
                    "%dyno.commands.base.create.success", [Utils::clean($base)]
                ));
            } catch (BaseAlreadyExistException $e) {
                $sender->sendMessage(new TranslationContainer(TextFormat::RED .
                    "%dyno.base.alreadyexist", [$base]
                ));
            }
            return true;
        } elseif (strtolower($args[0]) == "remove") {
            if (!isset($args[1])) {
                $this->baseHelp($sender);
                return true;
            }
            unset($args[0]);
            $base = implode(" ", $args);
            try {
                $server->getBases()->removeBase($base);
                $sender->sendMessage(new TranslationContainer(TextFormat::GREEN .
                    "%dyno.commands.base.remove.success", [Utils::clean($base)]
                ));
            } catch (NoBaseExistException $e) {
                $sender->sendMessage(new TranslationContainer(TextFormat::RED .
                    "%dyno.base.noexist", [Utils::clean($base)]
                ));
            }
            return true;
        } elseif (strtolower($args[0]) == "list") {
            $sender->sendMessage(new TranslationContainer(TextFormat::AQUA .
                "%dyno.commands.base.list.header", [count(($bases = $server->getBases()->getAllBasesName()))]
            ));
            foreach ($bases as $basename) {
                $sender->sendMessage($basename);
            }
            return true;
        } else {
            $this->baseHelp($sender);
            return true;
        }
    }

    /**
     * @param CommandSender $sender
     */
    public function baseHelp(CommandSender $sender)
    {
        $sender->sendMessage(new TranslationContainer("%dyno.commands.base.header"));
        $sender->sendMessage(new TranslationContainer("%dyno.commands.base.create.usage"));
        $sender->sendMessage(new TranslationContainer("%dyno.commands.base.remove.usage"));
        $sender->sendMessage(new TranslationContainer("%dyno.commands.base.list.usage"));
    }
}