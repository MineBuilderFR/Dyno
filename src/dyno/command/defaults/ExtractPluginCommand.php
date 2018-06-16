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
use dyno\plugin\{
    PharPluginLoader, Plugin
};
use dyno\Server;
use dyno\utils\TextFormat;

class ExtractPluginCommand extends VanillaCommand
{

    /**
     * ExtractPluginCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "Extracts the source code from a Phar plugin",
            "/extractplugin <pluginName>"
        );
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool|mixed
     * @throws \ReflectionException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->usageMessage);
            return true;
        }

        $pluginName = trim(implode(" ", $args));
        if ($pluginName === "" or !(($plugin = Server::getInstance()->getPluginManager()->getPlugin($pluginName)) instanceof Plugin)) {
            $sender->sendMessage(TextFormat::RED . "Invalid plugin name, check the file is in the plugin directory.");
            return true;
        }
        $description = $plugin->getDescription();

        if (!($plugin->getPluginLoader() instanceof PharPluginLoader)) {
            $sender->sendMessage(TextFormat::RED . "Plugin " . $description->getName() . " is not in Phar structure.");
            return true;
        }

        $folderPath = Server::getInstance()->getPluginPath() . DIRECTORY_SEPARATOR . "Dyno" . DIRECTORY_SEPARATOR . $description->getName() . "_v" . $description->getVersion() . "/";
        if (file_exists($folderPath)) {
            $sender->sendMessage("Plugin already exists, overwriting...");
        } else {
            @mkdir($folderPath);
        }

        $reflection = new \ReflectionClass("dyno\\plugin\\PluginBase");
        $file = $reflection->getProperty("file");
        $file->setAccessible(true);
        $pharPath = str_replace("\\", "/", rtrim($file->getValue($plugin), "\\/"));

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pharPath)) as $fInfo) {
            $path = $fInfo->getPathname();
            @mkdir(dirname($folderPath . str_replace($pharPath, "", $path)), 0755, true);
            file_put_contents($folderPath . str_replace($pharPath, "", $path), file_get_contents($path));
        }
        $sender->sendMessage("Source plugin " . $description->getName() . " v" . $description->getVersion() . " has been created on " . $folderPath);
        return true;
    }
}
