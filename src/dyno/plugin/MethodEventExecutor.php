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

use dyno\event\Event;
use dyno\event\Listener;

class MethodEventExecutor implements EventExecutor
{

    private $method;

    public function __construct($method)
    {
        $this->method = $method;
    }

    public function execute(Listener $listener, Event $event)
    {
        $listener->{$this->getMethod()}($event);
    }

    public function getMethod()
    {
        return $this->method;
    }
}