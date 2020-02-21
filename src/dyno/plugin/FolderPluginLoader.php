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

namespace dyno\plugin;

use dyno\Server;
use dyno\utils\MainLogger;
use dyno\utils\TextFormat;

class FolderPluginLoader implements PluginLoader
{

    /** @var Server */
    private $server;

    /**
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Loads the plugin contained in $file
     *
     * @param string $file
     *
     * @return Plugin
     */
    public function loadPlugin($file)
    {
        if (is_dir($file) and file_exists($file . "/plugin.yml") and file_exists($file . "/src/")) {
            if (($description = $this->getPluginDescription($file)) instanceof PluginDescription) {
                MainLogger::getLogger()->info(TextFormat::LIGHT_PURPLE . "读取中... " . $description->getFullName());
                $dataFolder = dirname($file) . DIRECTORY_SEPARATOR . $description->getName();
                if (file_exists($dataFolder) and !is_dir($dataFolder)) {
                    trigger_error("项目数据目录 '" . $dataFolder . "' 给 " . $description->getName() . " 已存在但不是一个目录", E_USER_WARNING);

                    return null;
                }


                $className = $description->getMain();
                $this->server->getLoader()->addPath($file . "/src");

                if (class_exists($className, true)) {
                    $plugin = new $className();
                    $this->initPlugin($plugin, $description, $dataFolder, $file);

                    return $plugin;
                } else {
                    trigger_error("无法加载源码插件 " . $description->getName() . "：未找到主类", E_USER_WARNING);

                    return null;
                }
            }
        }

        return null;
    }

    /**
     * Gets the PluginDescription from the file
     *
     * @param string $file
     *
     * @return PluginDescription
     */
    public function getPluginDescription($file)
    {
        if (is_dir($file) and file_exists($file . "/plugin.yml")) {
            $yaml = @file_get_contents($file . "/plugin.yml");
            if ($yaml != "") {
                return new PluginDescription($yaml);
            }
        }

        return null;
    }

    /**
     * @param PluginBase $plugin
     * @param PluginDescription $description
     * @param string $dataFolder
     * @param string $file
     */
    private function initPlugin(PluginBase $plugin, PluginDescription $description, $dataFolder, $file)
    {
        $plugin->init($this, $this->server, $description, $dataFolder, $file);
        $plugin->onLoad();
    }

    /**
     * Returns the filename patterns that this loader accepts
     *
     * @return string
     */
    public function getPluginFilters()
    {
        return "/[^\\.]/";
    }

    /**
     * @param Plugin $plugin
     */
    public function enablePlugin(Plugin $plugin)
    {
        if ($plugin instanceof PluginBase and !$plugin->isEnabled()) {
            MainLogger::getLogger()->info("Disabling " . $plugin->getDescription()->getFullName() . "...");
            $plugin->setEnabled(true);
        }
    }

    /**
     * @param Plugin $plugin
     */
    public function disablePlugin(Plugin $plugin)
    {
        if ($plugin instanceof PluginBase and $plugin->isEnabled()) {
            MainLogger::getLogger()->info("Disabling " . $plugin->getDescription()->getFullName() . "...");
            $plugin->setEnabled(false);
        }
    }
}
