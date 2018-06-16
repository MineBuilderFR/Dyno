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

namespace dyno\plugin;

/**
 * Handles different types of plugins
 */
interface PluginLoader
{

    /**
     * Loads the plugin contained in $file
     *
     * @param string $file
     *
     * @return Plugin
     */
    public function loadPlugin($file);

    /**
     * Gets the PluginDescription from the file
     *
     * @param string $file
     *
     * @return PluginDescription
     */
    public function getPluginDescription($file);

    /**
     * Returns the filename patterns that this loader accepts
     *
     * @return string[]
     */
    public function getPluginFilters();

    /**
     * @param Plugin $plugin
     *
     * @return void
     */
    public function enablePlugin(Plugin $plugin);

    /**
     * @param Plugin $plugin
     *
     * @return void
     */
    public function disablePlugin(Plugin $plugin);


}