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

use dyno\Server;

/**
 * Class used to run async tasks in other threads.
 *
 * WARNING: Do not call PocketMine-MP API methods, or save objects from/on other Threads!!
 */
abstract class AsyncTask extends \Threaded implements \Collectable
{

    /** @var AsyncWorker $worker */
    public $worker = null;
    /** @var null */
    private $result = null;
    /** @var bool */
    private $serialized = false;
    /** @var bool */
    private $cancelRun = false;
    /** @var int */
    private $taskId = null;
    /** @var bool */
    private $crashed = false;
    /** @var bool */
    private $isGarbage = false;
    /** @var bool */
    private $isFinished = false;

    public function setGarbage()
    {
        $this->isGarbage = true;
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->isFinished;
    }

    public function run()
    {
        $this->result = null;
        $this->isGarbage = false;

        if ($this->cancelRun !== true) {
            try {
                $this->onRun();
            } catch (\Throwable $e) {
                $this->crashed = true;
                $this->worker->handleException($e);
            }
        }

        $this->isFinished = true;
        //$this->setGarbage();
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public abstract function onRun();

    /**
     * @return bool
     */
    public function isCrashed()
    {
        return $this->crashed;
    }

    /**
     * @return mixed|null
     */
    public function getResult()
    {
        return $this->serialized ? unserialize($this->result) : $this->result;
    }

    /**
     * @param mixed $result
     * @param bool $serialize
     */
    public function setResult($result, $serialize = true)
    {
        $this->result = $serialize ? serialize($result) : $result;
        $this->serialized = $serialize;
    }

    public function cancelRun()
    {
        $this->cancelRun = true;
    }

    public function hasCancelledRun()
    {
        return $this->cancelRun === true;
    }

    /**
     * @return bool
     */
    public function hasResult()
    {
        return $this->result !== null;
    }

    public function getTaskId()
    {
        return $this->taskId;
    }

    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;
    }

    /**
     * Gets something into the local thread store.
     * You have to initialize this in some way from the task on run
     *
     * @param string $identifier
     * @return mixed
     */
    public function getFromThreadStore($identifier)
    {
        global $store;
        return $this->isGarbage() ? null : $store[$identifier];
    }

    /**
     * @return bool
     */
    public function isGarbage(): bool
    {
        return $this->isGarbage;
    }

    /**
     * Saves something into the local thread store.
     * This might get deleted at any moment.
     *
     * @param string $identifier
     * @param mixed $value
     */
    public function saveToThreadStore($identifier, $value)
    {
        global $store;
        if (!$this->isGarbage()) {
            $store[$identifier] = $value;
        }
    }

    /**
     * Actions to execute when completed (on main thread)
     * Implement this if you want to handle the data in your AsyncTask after it has been processed
     *
     * @param Server $server
     *
     * @return void
     */
    public function onCompletion(Server $server) { }

    public function cleanObject()
    {
        foreach ($this as $p => $v) {
            if (!($v instanceof \Threaded) and !in_array($p, ["isFinished", "isGarbage", "cancelRun"])) {
                $this->{$p} = null;
            }
        }
    }
}