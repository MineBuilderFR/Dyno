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

use dyno\event\Cancellable;
use dyno\event\Event;
use dyno\event\Listener;

class RegisteredListener
{

    /** @var Listener */
    private $listener;

    /** @var int */
    private $priority;

    /** @var Plugin */
    private $plugin;

    /** @var EventExecutor */
    private $executor;

    /** @var bool */
    private $ignoreCancelled;

    /**
     * @param Listener $listener
     * @param EventExecutor $executor
     * @param int $priority
     * @param Plugin $plugin
     * @param boolean $ignoreCancelled
     */
    public function __construct(Listener $listener, EventExecutor $executor, $priority, Plugin $plugin, $ignoreCancelled)
    {
        $this->listener = $listener;
        $this->priority = $priority;
        $this->plugin = $plugin;
        $this->executor = $executor;
        $this->ignoreCancelled = $ignoreCancelled;
    }

    /**
     * @return Listener
     */
    public function getListener()
    {
        return $this->listener;
    }

    /**
     * @return Plugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param Event $event
     */
    public function callEvent(Event $event)
    {
        if ($event instanceof Cancellable and $event->isCancelled() and $this->isIgnoringCancelled()) {
            return;
        }
        $this->executor->execute($this->listener, $event);
    }

    /**
     * @return bool
     */
    public function isIgnoringCancelled()
    {
        return $this->ignoreCancelled === true;
    }

    public function __destruct() { }
}