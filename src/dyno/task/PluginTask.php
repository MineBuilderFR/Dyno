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

namespace dyno\task;

use dyno\plugin\Plugin;

/**
 * Base class for plugin tasks. Allows the Server to delete them easily when needed
 */
abstract class PluginTask extends Task
{

    /** @var Plugin */
    protected $owner;

    /**
     * @param Plugin $owner
     */
    public function __construct(Plugin $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return Plugin
     */
    public final function getOwner(): Plugin
    {
        return $this->owner;
    }

}
