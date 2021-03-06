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
 * Task scheduling related classes
 */

namespace dyno\task;

use dyno\exception\PluginException;
use dyno\plugin\Plugin;
use dyno\Server;
use dyno\utils\MainLogger;
use dyno\utils\ReversePriorityQueue;

class ServerScheduler
{
    /** @var int */
    public static $WORKERS = 2;
    /** @var ReversePriorityQueue */
    protected $queue;
    /** @var TaskHandler[] */
    protected $tasks = [];
    /** @var AsyncPool */
    protected $asyncPool;
    /** @var int */
    protected $currentTick = 0;
    /** @var int */
    private $ids = 1;

    public function __construct()
    {
        $this->queue = new ReversePriorityQueue();
        $this->asyncPool = new AsyncPool(Server::getInstance(), self::$WORKERS);
    }

    /**
     * @param Task $task
     *
     * @return null|TaskHandler
     */
    public function scheduleTask(Task $task)
    {
        return $this->addTask($task, -1, -1);
    }

    /**
     * @param Task $task
     * @param      $delay
     * @param      $period
     *
     * @return null|TaskHandler
     *
     * @throws PluginException
     */
    private function addTask(Task $task, $delay, $period)
    {
        if ($task instanceof PluginTask) {
            if (!($task->getOwner() instanceof Plugin)) {
                throw new PluginException("Invalid owner of PluginTask " . get_class($task));
            } elseif (!$task->getOwner()->isEnabled()) {
                throw new PluginException("Plugin '" . $task->getOwner()->getName() . "' attempted to register a task while disabled");
            }
        }
        if ($delay <= 0) {
            $delay = -1;
        }

        if ($period <= -1) {
            $period = -1;
        } elseif ($period < 1) {
            $period = 1;
        }

        return $this->handle(new TaskHandler(get_class($task), $task, $this->nextId(), $delay, $period));
    }

    /**
     * @param TaskHandler $handler
     * @return TaskHandler
     */
    private function handle(TaskHandler $handler)
    {
        if ($handler->isDelayed()) {
            $nextRun = $this->currentTick + $handler->getDelay();
        } else {
            $nextRun = $this->currentTick;
        }

        $handler->setNextRun($nextRun);
        $this->tasks[$handler->getTaskId()] = $handler;
        $this->queue->insert($handler, $nextRun);

        return $handler;
    }

    /**
     * @return int
     */
    private function nextId(): int
    {
        return $this->ids++;
    }

    /**
     * Submits an asynchronous task to the Worker Pool
     *
     * @param AsyncTask $task
     *
     * @return void
     */
    public function scheduleAsyncTask(AsyncTask $task)
    {
        $id = $this->nextId();
        $task->setTaskId($id);
        $this->asyncPool->submitTask($task);
    }

    /**
     * Submits an asynchronous task to a specific Worker in the Pool
     *
     * @param AsyncTask $task
     * @param int $worker
     *
     * @return void
     */
    public function scheduleAsyncTaskToWorker(AsyncTask $task, $worker)
    {
        $id = $this->nextId();
        $task->setTaskId($id);
        $this->asyncPool->submitTaskToWorker($task, $worker);
    }

    /**
     * @return int
     */
    public function getAsyncTaskPoolSize(): int
    {
        return $this->asyncPool->getSize();
    }

    /**
     * @param int $newSize
     */
    public function increaseAsyncTaskPoolSize(int $newSize)
    {
        $this->asyncPool->increaseSize($newSize);
    }

    /**
     * @param Task $task
     * @param int $delay
     *
     * @return null|TaskHandler
     */
    public function scheduleDelayedTask(Task $task, $delay)
    {
        return $this->addTask($task, (int)$delay, -1);
    }

    /**
     * @param Task $task
     * @param int $period
     *
     * @return null|TaskHandler
     */
    public function scheduleRepeatingTask(Task $task, $period)
    {
        return $this->addTask($task, -1, (int)$period);
    }

    /**
     * @param Task $task
     * @param int $delay
     * @param int $period
     *
     * @return null|TaskHandler
     */
    public function scheduleDelayedRepeatingTask(Task $task, $delay, $period)
    {
        return $this->addTask($task, (int)$delay, (int)$period);
    }

    /**
     * @param int $taskId
     */
    public function cancelTask($taskId)
    {
        if ($taskId !== null and isset($this->tasks[$taskId])) {
            $this->tasks[$taskId]->cancel();
            unset($this->tasks[$taskId]);
        }
    }

    /**
     * @param Plugin $plugin
     */
    public function cancelTasks(Plugin $plugin)
    {
        foreach ($this->tasks as $taskId => $task) {
            $ptask = $task->getTask();
            if ($ptask instanceof PluginTask and $ptask->getOwner() === $plugin) {
                $task->cancel();
                unset($this->tasks[$taskId]);
            }
        }
    }

    public function cancelAllTasks()
    {
        foreach ($this->tasks as $task) {
            $task->cancel();
        }
        $this->tasks = [];
        $this->asyncPool->removeTasks();
        while (!$this->queue->isEmpty()) {
            $this->queue->extract();
        }
        $this->ids = 1;
    }

    /**
     * @param int $taskId
     *
     * @return bool
     */
    public function isQueued($taskId)
    {
        return isset($this->tasks[$taskId]);
    }

    /**
     * @param int $currentTick
     */
    public function mainThreadHeartbeat($currentTick)
    {
        $this->currentTick = $currentTick;
        while ($this->isReady($this->currentTick)) {
            /** @var TaskHandler $task */
            $task = $this->queue->extract();
            if ($task->isCancelled()) {
                unset($this->tasks[$task->getTaskId()]);
                continue;
            } else {
                try {
                    $task->run($this->currentTick);
                } catch (\Throwable $e) {
                    Server::getInstance()->getLogger()->critical("Could not execute task " . $task->getTaskName() . ": " . $e->getMessage());
                    $logger = Server::getInstance()->getLogger();
                    if ($logger instanceof MainLogger) {
                        $logger->logException($e);
                    }
                }
            }
            if ($task->isRepeating()) {
                $task->setNextRun($this->currentTick + $task->getPeriod());
                $this->queue->insert($task, $this->currentTick + $task->getPeriod());
            } else {
                $task->remove();
                unset($this->tasks[$task->getTaskId()]);
            }
        }

        $this->asyncPool->collectTasks();
    }

    private function isReady($currentTicks)
    {
        return count($this->tasks) > 0 and $this->queue->current()->getNextRun() <= $currentTicks;
    }

}
