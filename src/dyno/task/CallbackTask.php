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


namespace dyno\task;

/**
 * Allows the creation of simple callbacks with extra data
 * The last parameter in the callback will be this object
 *
 * If you want to do a task in a Plugin, consider extending PluginTask to your needs
 */
class CallbackTask extends Task
{

    /** @var callable */
    protected $callable;
    /** @var array */
    protected $args;

    /**
     * @param callable $callable
     * @param array $args
     */
    public function __construct(callable $callable, array $args = [])
    {
        $this->callable = $callable;
        $this->args = $args;
        $this->args[] = $this;
    }

    /**
     * @return callable
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }

    public function onRun(int $currentTicks)
    {
        call_user_func_array($this->callable, $this->args);
    }
}
