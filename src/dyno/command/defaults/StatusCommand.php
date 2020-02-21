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
use dyno\utils\{
    TextFormat, Utils
};

class StatusCommand extends VanillaCommand
{

    /**
     * StatusCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "%dyno.command.status.description",
            "%dyno.command.status.usage"
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
        $mUsage = Utils::getMemoryUsage(true);
        $rUsage = Utils::getRealMemoryUsage();

        $server = $sender->getServer();
        $sender->sendMessage(TextFormat::GREEN . "---- " . TextFormat::WHITE . "%dyno.command.status.title" . TextFormat::GREEN . " ----");

        $time = microtime(true) - \dyno\START_TIME;

        $seconds = floor($time % 60);
        $minutes = null;
        $hours = null;
        $days = null;

        if ($time >= 60) {
            $minutes = floor(($time % 3600) / 60);
            if ($time >= 3600) {
                $hours = floor(($time % (3600 * 24)) / 3600);
                if ($time >= 3600 * 24) {
                    $days = floor($time / (3600 * 24));
                }
            }
        }

        $uptime = ($minutes !== null ?
                ($hours !== null ?
                    ($days !== null ?
                        "$days %dyno.command.status.days "
                        : "") . "$hours %dyno.command.status.hours "
                    : "") . "$minutes %dyno.command.status.minutes "
                : "") . "$seconds %dyno.command.status.seconds";

        $sender->sendMessage(TextFormat::GOLD . "%dyno.command.status.uptime " . TextFormat::RED . $uptime);

        $tpsColor = TextFormat::GREEN;
        if ($server->getTicksPerSecondAverage() < 10) {
            $tpsColor = TextFormat::GOLD;
        } elseif ($server->getTicksPerSecondAverage() < 1) {
            $tpsColor = TextFormat::RED;
        }

        $tpsColour = TextFormat::GREEN;
        if ($server->getTicksPerSecond() < 10) {
            $tpsColour = TextFormat::GOLD;
        } elseif ($server->getTicksPerSecond() < 1) {
            $tpsColour = TextFormat::RED;
        }

        $sender->sendMessage(TextFormat::GOLD . "%dyno.command.status.AverageTPS " . $tpsColor . $server->getTicksPerSecondAverage() . " (" . $server->getTickUsageAverage() . "%)");
        $sender->sendMessage(TextFormat::GOLD . "%dyno.command.status.CurrentTPS " . $tpsColour . $server->getTicksPerSecond() . " (" . $server->getTickUsage() . "%)");

        $sender->sendMessage(TextFormat::GOLD . "%dyno.command.status.Threadcount " . TextFormat::RED . Utils::getThreadCount());

        $sender->sendMessage(TextFormat::GOLD . "%dyno.command.status.Mainmemory " . TextFormat::RED . number_format(round(($mUsage[0] / 1024) / 1024, 2)) . " MB.");
        $sender->sendMessage(TextFormat::GOLD . "%dyno.command.status.Totalmemory " . TextFormat::RED . number_format(round(($mUsage[1] / 1024) / 1024, 2)) . " MB.");
        $sender->sendMessage(TextFormat::GOLD . "%dyno.command.status.Totalvirtualmemory " . TextFormat::RED . number_format(round(($mUsage[2] / 1024) / 1024, 2)) . " MB.");
        $sender->sendMessage(TextFormat::GOLD . "%dyno.command.status.Heapmemory " . TextFormat::RED . number_format(round(($rUsage[0] / 1024) / 1024, 2)) . " MB.");
        $sender->sendMessage(TextFormat::GOLD . "%dyno.command.status.Maxmemorysystem " . TextFormat::RED . number_format(round(($mUsage[2] / 1024) / 1024, 2)) . " MB.");

        return true;
    }
}
