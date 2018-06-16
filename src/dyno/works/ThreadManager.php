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

namespace dyno\works;

class ThreadManager extends \Volatile
{
    /** @var ThreadManager */
    private static $instance = null;

    public static function init()
    {
        self::$instance = new ThreadManager();
    }

    /**
     * @return ThreadManager
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @param Worker|Thread $thread
     */
    public function add($thread)
    {
        if ($thread instanceof Thread or $thread instanceof Worker) {
            $this->{spl_object_hash($thread)} = $thread;
        }
    }

    /**
     * @param Worker|Thread $thread
     */
    public function remove($thread)
    {
        if ($thread instanceof Thread or $thread instanceof Worker) {
            unset($this->{spl_object_hash($thread)});
        }
    }

    /**
     * @return Worker[]|Thread[]
     */
    public function getAll(): array
    {
        $array = [];
        foreach ($this as $key => $thread) {
            $array[$key] = $thread;
        }
        return $array;
    }
}