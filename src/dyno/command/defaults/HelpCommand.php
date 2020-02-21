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

use dyno\command\{
    Command, CommandSender, ConsoleCommandSender
};
use dyno\event\TranslationContainer;
use dyno\utils\TextFormat;

class HelpCommand extends VanillaCommand
{

    /**
     * HelpCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "%dyno.command.help.description",
            "%dyno.commands.help.usage",
            ["?"]
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
        if (count($args) === 0) {
            $command = "";
            $pageNumber = 1;
        } elseif (is_numeric($args[count($args) - 1])) {
            $pageNumber = (int)array_pop($args);
            if ($pageNumber <= 0) {
                $pageNumber = 1;
            }
            $command = implode(" ", $args);
        } else {
            $command = implode(" ", $args);
            $pageNumber = 1;
        }

        if ($sender instanceof ConsoleCommandSender) {
            $pageHeight = PHP_INT_MAX;
        } else {
            $pageHeight = 5;
        }

        if ($command === "") {
            /** @var Command[][] $commands */
            $commands = [];
            foreach ($sender->getServer()->getCommandMap()->getCommands() as $command) {
                $commands[$command->getName()] = $command;
            }
            ksort($commands, SORT_NATURAL | SORT_FLAG_CASE);
            $commands = array_chunk($commands, $pageHeight);
            $pageNumber = (int)min(count($commands), $pageNumber);
            if ($pageNumber < 1) {
                $pageNumber = 1;
            }
            $sender->sendMessage(new TranslationContainer("dyno.commands.help.header", [$pageNumber, count($commands)]));
            if (isset($commands[$pageNumber - 1])) {
                foreach ($commands[$pageNumber - 1] as $command) {
                    $sender->sendMessage(TextFormat::DARK_GREEN . "/" . $command->getName() . ": " . TextFormat::WHITE . $command->getDescription());
                }
            }

            return true;
        } else {
            if (($cmd = $sender->getServer()->getCommandMap()->getCommand(strtolower($command))) instanceof Command) {
                $message = TextFormat::YELLOW . "--------- " . TextFormat::WHITE . " Help: /" . $cmd->getName() . TextFormat::YELLOW . " ---------\n";
                $message .= TextFormat::GOLD . "Description: " . TextFormat::WHITE . $cmd->getDescription() . "\n";
                $message .= TextFormat::GOLD . "Usage: " . TextFormat::WHITE . implode("\n" . TextFormat::WHITE, explode("\n", $cmd->getUsage())) . "\n";
                $sender->sendMessage($message);

                return true;
            }
            $sender->sendMessage(TextFormat::RED . "No help for " . strtolower($command));

            return true;
        }
    }

}