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

namespace dyno\command;

use dyno\utils\{
    MainLogger, Utils
};
use dyno\works\Thread;

class CommandReader extends Thread
{
    /** @var \Threaded */
    protected $buffer;
    /** @var bool */
    private $readline;
    /** @var bool */
    private $shutdown = false;
    /** @var bool|resource */
    private $stdin;
    /** @var MainLogger */
    private $logger;

    /**
     * CommandReader constructor.
     * @param MainLogger $logger
     */
    public function __construct(MainLogger $logger)
    {
        $this->stdin = fopen("php://stdin", "r");
        $opts = getopt("", ["disable-readline"]);
        if (extension_loaded("readline") && !isset($opts["disable-readline"]) && (!function_exists("posix_isatty") || posix_isatty($this->stdin))) {
            $this->readline = true;
        } else {
            $this->readline = false;
        }
        $this->logger = $logger;
        $this->buffer = new \Threaded;
        $this->start();
    }

    /**
     * Reads a line from console, if available. Returns null if not available
     *
     * @return string|null
     */
    public function getLine(): ?string
    {
        if ($this->buffer->count() !== 0) {
            return $this->buffer->shift();
        }

        return null;
    }

    public function quit()
    {
        $this->shutdown();
        // Windows sucks
        if (Utils::getOS() != "win") {
            parent::quit();
        }
    }

    public function shutdown()
    {
        $this->shutdown = true;
    }

    public function run()
    {
        if ($this->readline) {
            readline_callback_handler_install("Synapse> ", [$this, "readline_callback"]);
            $this->logger->setConsoleCallback("readline_redisplay");
        }

        while (!$this->shutdown) {
            $r = [$this->stdin];
            $w = null;
            $e = null;
            if (stream_select($r, $w, $e, 0, 200000) > 0) {
                // PHP on Windows sucks
                if (feof($this->stdin)) {
                    if (Utils::getOS() == "win") {
                        $this->stdin = fopen("php://stdin", "r");
                        if (!is_resource($this->stdin)) {
                            break;
                        }
                    } else {
                        break;
                    }
                }
                $this->readLine();
            }
        }

        if ($this->readline) {
            $this->logger->setConsoleCallback(null);
            readline_callback_handler_remove();
        }
    }

    private function readLine()
    {
        if (!$this->readline) {
            $line = trim(fgets($this->stdin));
            if ($line !== "") {
                $this->buffer[] = $line;
            }
        } else {
            readline_callback_read_char();
        }
    }

    /**
     * @return string
     */
    public function getThreadName(): string
    {
        return "Console";
    }

    /**
     * @param string $line
     */
    private function readline_callback(string $line)
    {
        if ($line !== "") {
            $this->buffer[] = $line;
            readline_add_history($line);
        }
    }
}
