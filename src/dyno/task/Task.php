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

abstract class Task
{

    /** @var TaskHandler */
    private $taskHandler = null;

    /**
     * @return TaskHandler
     */
    public final function getHandler(): TaskHandler
    {
        return $this->taskHandler;
    }

    /**
     * @return int
     */
    public final function getTaskId(): int
    {
        if ($this->taskHandler !== null) {
            return $this->taskHandler->getTaskId();
        }

        return -1;
    }

    /**
     * @param TaskHandler|null $taskHandler
     */
    public final function setHandler(?TaskHandler $taskHandler)
    {
        if ($this->taskHandler === null or $taskHandler === null) {
            $this->taskHandler = $taskHandler;
        }
    }

    /**
     * Actions to execute when run
     *
     * @param $currentTick
     *
     * @return void
     */
    public abstract function onRun(int $currentTick);

    /**
     * Actions to execute if the Task is cancelled
     */
    public function onCancel() { }

}
