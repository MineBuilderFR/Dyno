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

/**
 * Event related classes
 */

namespace dyno\event;

abstract class Event
{

    /**
     * Any callable event must declare the static variable
     *
     * public static $handlerList = null;
     * public static $eventPool = [];
     * public static $nextEvent = 0;
     *
     * Not doing so will deny the proper event initialization
     */

    protected $eventName = null;
    private $isCancelled = false;

    /**
     * @return string
     */
    final public function getEventName()
    {
        return $this->eventName === null ? get_class($this) : $this->eventName;
    }

    /**
     * @return bool
     *
     * @throws \BadMethodCallException
     */
    public function isCancelled()
    {
        if (!($this instanceof Cancellable)) {
            throw new \BadMethodCallException("Event is not Cancellable");
        }

        /** @var Event $this */
        return $this->isCancelled === true;
    }

    /**
     * @param bool $value
     *
     * @return void
     *
     */
    public function setCancelled($value = true)
    {
        if (!($this instanceof Cancellable)) {
            throw new \BadMethodCallException("Event is not Cancellable");
        }

        /** @var Event $this */
        $this->isCancelled = (bool)$value;
    }

    /**
     * @return HandlerList
     */
    public function getHandlers()
    {
        if (static::$handlerList === null) {
            static::$handlerList = new HandlerList();
        }
        return static::$handlerList;
    }

}