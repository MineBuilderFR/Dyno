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
use dyno\task\GarbageCollectionTask;
use dyno\utils\TextFormat;

class GarbageCollectorCommand extends VanillaCommand
{

    /**
     * GarbageCollectorCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "%dyno.command.gc.description",
            "%dyno.command.gc.usage"
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
        $size = $sender->getServer()->getScheduler()->getAsyncTaskPoolSize();
        for ($i = 0; $i < $size; ++$i) {
            $sender->getServer()->getScheduler()->scheduleAsyncTaskToWorker(new GarbageCollectionTask(), $i);
        }
        $sender->sendMessage(TextFormat::GOLD . "Collected cycles: " . gc_collect_cycles());
        return true;
    }
}
