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
use dyno\network\packages\Info;
use dyno\Server;

class MakeServerCommand extends VanillaCommand
{

    /**
     * MakeServerCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            "Creates a Dyno Phar",
            "/makeserver (nogz)"
        );
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {

        $server = $sender->getServer();
        $pharPath = Server::getInstance()->getPluginPath() . DIRECTORY_SEPARATOR . "Dyno" . DIRECTORY_SEPARATOR . $server->getName() . "_" . $server->getDynoVersion() . ".phar";
        if (file_exists($pharPath)) {
            $sender->sendMessage("Phar file already exists, overwriting...");
            @unlink($pharPath);
        }
        $phar = new \Phar($pharPath);
        $phar->setMetadata([
            "name" => $server->getName(),
            "version" => $server->getDynoVersion(),
            "api" => $server->getApiVersion(),
            "minecraft" => $server->getVersion(),
            "protocol" => Info::CURRENT_PROTOCOL,
            "creator" => "Dyno MakeServerCommand",
            "creationDate" => time()
        ]);
        $phar->setStub('<?php define("dyno\\\\PATH", "phar://". __FILE__ ."/"); require_once("phar://". __FILE__ ."/src/dyno/Boot.php");  __HALT_COMPILER();');
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        $phar->startBuffering();

        $filePath = substr(\dyno\PATH, 0, 7) === "phar://" ? \dyno\PATH : realpath(\dyno\PATH) . "/";
        $filePath = rtrim(str_replace("\\", "/", $filePath), "/") . "/";
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath . "src")) as $file) {
            $path = ltrim(str_replace(["\\", $filePath], ["/", ""], $file), "/");
            if ($path{0} === "." or strpos($path, "/.") !== false or substr($path, 0, 4) !== "src/") {
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
        if (!isset($args[0]) or (isset($args[0]) and $args[0] != "nogz")) {
            $phar->compressFiles(\Phar::GZ);
        }
        $phar->stopBuffering();

        $sender->sendMessage($server->getName() . " " . $server->getDynoVersion() . " Phar file has been created on " . $pharPath);

        return true;
    }
}
