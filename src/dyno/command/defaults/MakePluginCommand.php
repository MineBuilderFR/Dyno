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
    FolderPluginLoader, Plugin
};
use dyno\Server;
use dyno\utils\TextFormat;

class MakePluginCommand extends VanillaCommand
{

    /**
     * MakePluginCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "Creates a Phar plugin from a unarchived",
            "/makeplugin <pluginName> (nogz)"
        );
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
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
            $sender->sendMessage(TextFormat::RED . "Invalid plugin name, check the name case.");
            return true;
        }
        $description = $plugin->getDescription();

        if (!($plugin->getPluginLoader() instanceof FolderPluginLoader)) {
            $sender->sendMessage(TextFormat::RED . "Plugin " . $description->getName() . " is not in folder structure.");
            return true;
        }

        $pharPath = Server::getInstance()->getPluginPath() . DIRECTORY_SEPARATOR . "Dyno" . DIRECTORY_SEPARATOR . $description->getName() . "_v" . $description->getVersion() . ".phar";
        if (file_exists($pharPath)) {
            $sender->sendMessage("Phar plugin already exists, overwriting...");
            @unlink($pharPath);
        }
        $phar = new \Phar($pharPath);
        $phar->setMetadata([
            "name" => $description->getName(),
            "version" => $description->getVersion(),
            "main" => $description->getMain(),
            "api" => $description->getCompatibleApis(),
            "depend" => $description->getDepend(),
            "description" => $description->getDescription(),
            "authors" => $description->getAuthors(),
            "website" => $description->getWebsite(),
            "creator" => "Dyno MakePluginCommand",
            "creationDate" => time()
        ]);
        $phar->setStub('<?php echo "Dyno plugin ' . $description->getName() . ' v' . $description->getVersion() . '\nThis file has been generated using Dyno by SugarGamesTeam Y&SS at ' . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();');
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        $reflection = new \ReflectionClass("dyno\\plugin\\PluginBase");
        $file = $reflection->getProperty("file");
        $file->setAccessible(true);
        $filePath = rtrim(str_replace("\\", "/", $file->getValue($plugin)), "/") . "/";
        $phar->startBuffering();
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath)) as $file) {
            $path = ltrim(str_replace(["\\", $filePath], ["/", ""], $file), "/");
            if ($path{0} === "." or strpos($path, "/.") !== false) {
                continue;
            }
            $phar->addFile($file, $path);
            $sender->sendMessage("[Dyno] Adding $path");
        }

        foreach ($phar as $file => $finfo) {
            /** @var \PharFileInfo $finfo */
            if ($finfo->getSize() > (1024 * 512)) {
                $finfo->compress(\Phar::GZ);
            }
        }
        if (!isset($args[1]) or (isset($args[1]) and $args[1] != "nogz")) {
            $phar->compressFiles(\Phar::GZ);
        }
        $phar->stopBuffering();
        $sender->sendMessage("Phar plugin " . $description->getName() . " v" . $description->getVersion() . " has been created on " . $pharPath);
        return true;
    }
}
