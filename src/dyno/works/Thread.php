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

namespace dyno\works;

use dyno\Server;

abstract class Thread extends \Thread
{
    /** @var \ClassLoader */
    protected $classLoader;
    /** @var bool */
    protected $isKilled = false;

    public function registerClassLoader()
    {
        if (!interface_exists("ClassLoader", false)) {
            require(\dyno\PATH . "src/spl/ClassLoader.php");
            require(\dyno\PATH . "src/spl/BaseClassLoader.php");
            require(\dyno\PATH . "src/dyno/works/CompatibleClassLoader.php");
        }
        if ($this->classLoader !== null) {
            $this->classLoader->register(true);
        }
    }

    /**
     * @param int $options
     * @return bool
     */
    public function start(int $options = PTHREADS_INHERIT_ALL)
    {
        ThreadManager::getInstance()->add($this);
        if (!$this->isRunning() and !$this->isJoined() and !$this->isTerminated()) {
            if ($this->getClassLoader() === null) {
                $this->setClassLoader();
            }
            return parent::start($options);
        }
        return false;
    }

    /**
     * @return \ClassLoader
     */
    public function getClassLoader()
    {
        return $this->classLoader;
    }

    /**
     * @param \ClassLoader|null $loader
     */
    public function setClassLoader(\ClassLoader $loader = null)
    {
        if ($loader === null) {
            $loader = Server::getInstance()->getLoader();
        }
        $this->classLoader = $loader;
    }

    /**
     * Stops the thread using the best way possible. Try to stop it yourself before calling this.
     */
    public function quit()
    {
        $this->isKilled = true;
        $this->notify();
        if (!$this->isJoined()) {
            if (!$this->isTerminated()) {
                $this->join();
            }
        }

        ThreadManager::getInstance()->remove($this);
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    public function getThreadName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
