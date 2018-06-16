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

class TaskHandler
{

    /** @var null|string */
    public $timingName = null;
    /** @var Task */
    protected $task;
    /** @var int */
    protected $taskId;
    /** @var int */
    protected $delay;
    /** @var int */
    protected $period;
    /** @var int */
    protected $nextRun;
    /** @var bool */
    protected $cancelled = false;

    /**
     * @param string $timingName
     * @param Task $task
     * @param int $taskId
     * @param int $delay
     * @param int $period
     */
    public function __construct(string $timingName, Task $task, int $taskId, int $delay = -1, int $period = -1)
    {
        $this->task = $task;
        $this->taskId = $taskId;
        $this->delay = $delay;
        $this->period = $period;
        $this->timingName = $timingName === null ? "Unknown" : $timingName;
    }

    /**
     * @return int
     */
    public function getNextRun(): int
    {
        return $this->nextRun;
    }

    /**
     * @param int $ticks
     */
    public function setNextRun($ticks)
    {
        $this->nextRun = $ticks;
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->taskId;
    }

    /**
     * @return Task
     */
    public function getTask(): Task
    {
        return $this->task;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * @return bool
     */
    public function isDelayed(): bool
    {
        return $this->delay > 0;
    }

    /**
     * @return bool
     */
    public function isRepeating(): bool
    {
        return $this->period > 0;
    }

    /**
     * @return int
     */
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * WARNING: Do not use this, it's only for internal use.
     * Changes to this function won't be recorded on the version.
     */
    public function cancel()
    {
        if (!$this->isCancelled()) {
            $this->task->onCancel();
        }
        $this->remove();
    }

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->cancelled === true;
    }

    public function remove()
    {
        $this->cancelled = true;
        $this->task->setHandler(null);
    }

    /**
     * @param int $currentTick
     */
    public function run(int $currentTick)
    {
        $this->task->onRun($currentTick);
    }

    /**
     * @return string
     */
    public function getTaskName(): string
    {
        if ($this->timingName !== null) {
            return $this->timingName;
        }

        return get_class($this->task);
    }
}
