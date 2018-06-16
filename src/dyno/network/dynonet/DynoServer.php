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

namespace dyno\network\dynonet;

use dyno\works\Thread;

class DynoServer extends Thread
{
    /** @var \ThreadedLogger */
    private $logger;
    /** @var string */
    private $interface;
    /** @var int */
    private $port;
    /** @var bool */
    private $shutdown = true;
    /** @var \Threaded */
    private
        $externalQueue,
        $internalQueue,
        $clientOpenQueue,
        $internalClientCloseQueue,
        $externalClientCloseQueue;
    /** @var string */
    private $mainPath;
    /** @var DynoInterface */
    private $server;

    /**
     * DynoServer constructor.
     * @param \ThreadedLogger $logger
     * @param DynoInterface $server
     * @param \ClassLoader $loader
     * @param int $port
     * @param string $interface
     * @throws \Exception
     */
    public function __construct(\ThreadedLogger $logger, DynoInterface $server,
                                \ClassLoader $loader, int $port, string $interface = "0.0.0.0")
    {
        $this->logger = $logger;
        $this->server = $server;
        $this->interface = $interface;
        $this->port = $port;
        if ($port < 1 or $port > 65536) {
            throw new \Exception("Invalid port range");
        }
        $this->setClassLoader($loader);

        $this->shutdown = false;
        $this->externalQueue = new \Threaded;
        $this->internalQueue = new \Threaded;
        $this->clientOpenQueue = new \Threaded;
        $this->internalClientCloseQueue = new \Threaded;
        $this->externalClientCloseQueue = new \Threaded;

        if (\Phar::running(true) !== "") {
            $this->mainPath = \Phar::running(true);
        } else {
            $this->mainPath = \getcwd() . DIRECTORY_SEPARATOR;
        }
        $this->start();
    }

    /**
     * @return bool|mixed
     */
    public function getInternalClientCloseRequest()
    {
        return $this->internalClientCloseQueue->shift();
    }

    /**
     * @param string $hash
     */
    public function addInternalClientCloseRequest(string $hash)
    {
        $this->internalClientCloseQueue[] = $hash;
    }

    /**
     * @return bool|mixed
     */
    public function getExternalClientCloseRequest()
    {
        return $this->externalClientCloseQueue->shift();
    }

    /**
     * @param string $hash
     */
    public function addExternalClientCloseRequest(string $hash)
    {
        $this->externalClientCloseQueue[] = $hash;
    }

    /**
     * @return bool|mixed
     */
    public function getClientOpenRequest()
    {
        return $this->clientOpenQueue->shift();
    }

    /**
     * @param string $hash
     */
    public function addClientOpenRequest(string $hash)
    {
        $this->clientOpenQueue[] = $hash;
    }

    /**
     * @return DynoInterface
     */
    public function getServer(): DynoInterface
    {
        return $this->server;
    }

    public function run()
    {
        $this->registerClassLoader();
        gc_enable();
        error_reporting(-1);
        ini_set("display_errors", '1');
        ini_set("display_startup_errors", '1');

        set_error_handler([$this, "errorHandler"], E_ALL);
        register_shutdown_function([$this, "shutdownHandler"]);

        try {
            $socket = new Socket($this->getLogger(), $this->port, $this->interface);
            new SessionManager($this, $socket);
        } catch (\Throwable $e) {
            $this->logger->logException($e);
        }
    }

    /**
     * @return \ThreadedLogger
     */
    public function getLogger(): \ThreadedLogger
    {
        return $this->logger;
    }

    public function quit()
    {
        $this->shutdown();
        parent::quit();
    }

    public function shutdown()
    {
        $this->shutdown = true;
    }

    public function shutdownHandler()
    {
        if ($this->shutdown !== true) {
            $this->getLogger()->emergency("Dyno crashed!");
        }
    }

    public function errorHandler($errno, $errstr, $errfile, $errline, $context, $trace = null)
    {
        if (error_reporting() === 0) {
            return false;
        }
        $errorConversion = [
            E_ERROR => "E_ERROR",
            E_WARNING => "E_WARNING",
            E_PARSE => "E_PARSE",
            E_NOTICE => "E_NOTICE",
            E_CORE_ERROR => "E_CORE_ERROR",
            E_CORE_WARNING => "E_CORE_WARNING",
            E_COMPILE_ERROR => "E_COMPILE_ERROR",
            E_COMPILE_WARNING => "E_COMPILE_WARNING",
            E_USER_ERROR => "E_USER_ERROR",
            E_USER_WARNING => "E_USER_WARNING",
            E_USER_NOTICE => "E_USER_NOTICE",
            E_STRICT => "E_STRICT",
            E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
            E_DEPRECATED => "E_DEPRECATED",
            E_USER_DEPRECATED => "E_USER_DEPRECATED",
        ];
        $errno = isset($errorConversion[$errno]) ? $errorConversion[$errno] : $errno;
        if (($pos = strpos($errstr, "\n")) !== false) {
            $errstr = substr($errstr, 0, $pos);
        }
        $errfile = $this->cleanPath($errfile);

        $this->getLogger()->debug("An $errno error happened: \"$errstr\" in \"$errfile\" at line $errline");

        foreach (($trace = $this->getTrace($trace === null ? 3 : 0, $trace)) as $i => $line) {
            $this->getLogger()->debug($line);
        }

        return true;
    }

    /**
     * @param string $path
     * @return string
     */
    public function cleanPath(string $path): string
    {
        return rtrim(str_replace(["\\", ".php", "phar://", rtrim(str_replace(["\\", "phar://"], ["/", ""], $this->mainPath), "/")], ["/", "", "", ""], $path), "/");
    }

    /**
     * @param int $start
     * @param array|null $trace
     * @return array
     */
    public function getTrace(int $start = 1, ?array $trace = null): array
    {
        if ($trace === null) {
            if (function_exists("xdebug_get_function_stack")) {
                $trace = array_reverse(xdebug_get_function_stack());
            } else {
                $e = new \Exception();
                $trace = $e->getTrace();
            }
        }

        $messages = [];
        $j = 0;
        for ($i = (int)$start; isset($trace[$i]); ++$i, ++$j) {
            $params = "";
            if (isset($trace[$i]["args"]) or isset($trace[$i]["params"])) {
                if (isset($trace[$i]["args"])) {
                    $args = $trace[$i]["args"];
                } else {
                    $args = $trace[$i]["params"];
                }
                foreach ($args as $name => $value) {
                    $params .= (is_object($value) ? get_class($value) . " " . (method_exists($value, "__toString") ? $value->__toString() : "object") : gettype($value) . " " . @strval($value)) . ", ";
                }
            }
            $messages[] = "#$j " . (isset($trace[$i]["file"]) ? $this->cleanPath($trace[$i]["file"]) : "") . "(" . (isset($trace[$i]["line"]) ? $trace[$i]["line"] : "") . "): " . (isset($trace[$i]["class"]) ? $trace[$i]["class"] . (($trace[$i]["type"] === "dynamic" or $trace[$i]["type"] === "->") ? "->" : "::") : "") . $trace[$i]["function"] . "(" . substr($params, 0, -2) . ")";
        }

        return $messages;
    }

    /**
     * @return \Threaded
     */
    public function getExternalQueue(): \Threaded
    {
        return $this->externalQueue;
    }

    /**
     * @return \Threaded
     */
    public function getInternalQueue(): \Threaded
    {
        return $this->internalQueue;
    }

    public function pushMainToThreadPacket($str)
    {
        $this->internalQueue[] = $str;
    }

    /**
     * @return bool|mixed
     */
    public function readMainToThreadPacket()
    {
        return $this->internalQueue->shift();
    }

    public function pushThreadToMainPacket($str)
    {
        $this->externalQueue[] = $str;
    }

    /**
     * @return bool|mixed
     */
    public function readThreadToMainPacket()
    {
        return $this->externalQueue->shift();
    }

    /**
     * @return bool
     */
    public function isShutdown(): bool
    {
        return $this->shutdown === true;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getInterface(): string
    {
        return $this->interface;
    }

    /**
     * @return bool
     */
    public function isGarbage(): bool
    {
        return parent::isGarbage();
    }

    /**
     * @return string
     */
    public function getThreadName(): string
    {
        return "DynoServer";
    }
}