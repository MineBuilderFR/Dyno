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

use dyno\works\Worker;

class AsyncWorker extends Worker
{

    /** @var \ThreadedLogger */
    private $logger;
    /** @var int */
    private $id;

    /**
     * AsyncWorker constructor.
     * @param \ThreadedLogger $logger
     * @param int $id
     */
    public function __construct(\ThreadedLogger $logger, int $id)
    {
        $this->logger = $logger;
        $this->id = $id;
    }

    public function run()
    {
        $this->registerClassLoader();
        gc_enable();
        ini_set("memory_limit", '-1');

        global $store;
        $store = [];
    }

    /**
     * @param \Throwable $e
     */
    public function handleException(\Throwable $e)
    {
        $this->logger->logException($e);
    }

    /**
     * @return string
     */
    public function getThreadName(): string
    {
        return "Asynchronous Worker #" . $this->id;
    }
}
