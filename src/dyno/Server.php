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

namespace dyno;

use dyno\command\{
    CommandReader, CommandSender, ConsoleCommandSender, SimpleCommandMap
};
use dyno\event\{
    HandlerList, TranslationContainer
};
use dyno\exception\{
    Mysql\MysqlConnectException, ServerException
};
use dyno\lang\BaseLang;
use dyno\manager\{
    Bases, Tables
};
use dyno\network\dynonet\DynoInterface;
use dyno\plugin\{
    FolderPluginLoader, PharPluginLoader, Plugin, PluginManager, ScriptPluginLoader
};
use dyno\sql\{
    MysqlDatabase, task\AutoDynToSqlTask
};
use dyno\task\ServerScheduler;
use dyno\utils\{
    Config, DynoTitle, MainLogger, Terminal, TextFormat, Utils, VersionString
};

class Server
{
    /** @var Server */
    private static $instance = null;
    /** @var int */
    private static $serverId = 0;
    /** @var \ClassLoader */
    private $autoloader;
    /** @var MainLogger */
    private $logger;
    /** @var string */
    private $filePath;
    /** @var string */
    private $pluginPath;
    /** @var BaseLang */
    private $baseLang;
    /** @var Config */
    private $properties;
    /** @var PluginManager */
    private $pluginManager;
    /** @var CommandReader */
    private $console;
    /** @var VersionString */
    private $version;
    /** @var ServerScheduler */
    private $scheduler;
    /** @var int */
    private $tickCounter;
    /** @var int */
    private $nextTick = 0;
    /** @var int[] */
    private $tickAverage = [
        100, 100, 100, 100, 100, 100, 100, 100, 100, 100,
        100, 100, 100, 100, 100, 100, 100, 100, 100, 100
    ];
    /** @var int[] */
    private $useAverage = [
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0
    ];
    /** @var int */
    private $maxTick = 100;
    /** @var int */
    private $maxUse = 0;
    /** @var bool */
    private $isRunning = true;
    /** @var bool */
    private $hasStopped = false;
    /** @var bool */
    private $dispatchSignals = false;
    /** @var DynoInterface */
    private $dynoInterface;
    /** @var Client[] */
    private $clients = [];
    /** @var array */
    private $clientData;
    /** @var Bases */
    private $bases;
    /** @var Tables */
    private $tables;
    /** @var SimpleCommandMap */
    private $commandMap;
    /** @var float */
    private $profilingTickRate;
    /** @var ConsoleCommandSender */
    private $consoleSender;
    /** @var string */
    private $dataPath;
    /** @var MysqlDatabase|null */
    private $database;
    /** @var Config */
    private $mysqlProperties;

    /**
     * Server constructor.
     * @param \ClassLoader $autoloader
     * @param MainLogger $logger
     * @param string $filePath
     * @param string $dataPath
     * @param string $pluginPath
     */
    public function __construct(\ClassLoader $autoloader, MainLogger $logger,
                                string $filePath, string $dataPath, string $pluginPath)
    {
        self::$instance = $this;
        self::$serverId = mt_rand(0, PHP_INT_MAX);
        $this->autoloader = $autoloader;
        $this->logger = $logger;
        $this->filePath = $filePath;
        $this->pluginPath = $pluginPath;
        try {
            if (!file_exists($pluginPath)) {
                mkdir($pluginPath, 0777);
            }

            $this->dataPath = realpath($dataPath) . DIRECTORY_SEPARATOR;
            $this->pluginPath = realpath($pluginPath) . DIRECTORY_SEPARATOR;

            $this->console = new CommandReader($logger);

            $version = new VersionString($this->getDynoVersion());
            $this->version = $version;
            echo(DynoTitle::TITLE);
            $this->logger->info(TextFormat::YELLOW . "Loading server properties...");
            $this->properties = new Config($this->dataPath . "server.properties", Config::PROPERTIES, [
                "dyno-port" => 10102,
                "password" => Utils::random_password(16),
                "lang" => "eng",
                "async-workers" => "auto",
                "profile-report-trigger" => 20,
                "strict-mode" => true,
                "log-packet-received" => false
            ]);
            $this->mysqlProperties = new Config($this->dataPath . "mysql.properties", Config::PROPERTIES, [
                "mysql-enabled" => false,
                "auto-send-dyn-to-sql" => false,
                "time-auto-send-in-second" => (60 * 2),
                "log-auto-send-sql" => true,

                "connection-host" => "localhost",
                "connection-port" => 3306,
                "connection-username" => "root",
                "connection-password" => "",
                "connection-database" => "dyno",
                "connection-socket" => ""
            ]);
            $this->baseLang = new BaseLang($this->getConfig("lang", BaseLang::FALLBACK_LANGUAGE));
            $this->logger->info($this->getLanguage()->translateString("language.selected", [$this->getLanguage()->getName(), $this->getLanguage()->getLang()]));

            $this->logger->info($this->getLanguage()->translateString("dyno.server.start"));

            if (($poolSize = $this->getConfig("async-workers", "auto")) === "auto") {
                $poolSize = ServerScheduler::$WORKERS;
                $processors = Utils::getCoreCount() - 2;

                if ($processors > 0) {
                    $poolSize = max(1, $processors);
                }
            }

            ServerScheduler::$WORKERS = $poolSize;

            $this->scheduler = new ServerScheduler();

            define('dyno\DEBUG', 2);

            if (\dyno\DEBUG >= 0) {
                @cli_set_process_title($this->getName() . " " . $this->getDynoVersion());
            }

            define("BOOTUP_RANDOM", @Utils::getRandomBytes(16));

            $this->logger->info($this->getLanguage()->translateString("dyno.server.info", [
                $this->getName(),
                $this->getDynoVersion(),
                $this->getCodename(),
                $this->getApiVersion()
            ]));
            $this->logger->info($this->getLanguage()->translateString("dyno.server.license", [$this->getName()]));

            $this->consoleSender = new ConsoleCommandSender();
            $this->commandMap = new SimpleCommandMap($this);

            $this->pluginManager = new PluginManager($this, $this->commandMap);
            $this->profilingTickRate = (float)$this->getConfig("profile-report-trigger", 20);
            $this->pluginManager->registerInterface(PharPluginLoader::class);
            $this->pluginManager->registerInterface(FolderPluginLoader::class);
            $this->pluginManager->registerInterface(ScriptPluginLoader::class);

            //register_shutdown_function([$this, "crashDump"]);

            $this->dynoInterface = new DynoInterface($this, $this->getIp(), $this->getDynoPort());

            $this->bases = new Bases($this);
            $this->tables = new Tables($this);

            $this->pluginManager->loadPlugins($this->pluginPath);

            $this->enablePlugins();

            if ((bool)$this->mysqlProperties->get("mysql-enabled", false) === true) {
                try {
                    $this->setDatabase(new MysqlDatabase($this));
                } catch (MysqlConnectException $e) {
                    $this->getLogger()->critical(
                        $this->getLanguage()->translate(
                            new TranslationContainer("%dyno.mysql.connection.could")
                        ) . ": {$e->getMessage()}");
                    $this->shutdown();
                    return;
                }

                if ((bool)$this->mysqlProperties->get("auto-send-dyn-to-sql", false) === true) {
                    $time = (int)$this->mysqlProperties->get("time-auto-send-in-second", (60 * 2));
                    if ($time < 10) $time = 10;
                    if ($time > PHP_INT_MAX) $time = (60 * 2);
                    $this->getScheduler()->scheduleRepeatingTask(new AutoDynToSqlTask(
                        $this,
                        $time,
                        (bool)$this->getProperties()->get("log-auto-send-sql", true)
                    ), 100);
                    $this->getLogger()->info(TextFormat::GREEN . $this->getLanguage()->translate(
                            new TranslationContainer("%dyno.mysql.connection.startAutoDynToSql", [
                                $time
                            ])
                        ));
                }
            }
            $this->start();

        } catch (\Throwable $e) {
            $this->exceptionHandler($e);
        }
    }

    /**
     * @return string
     */
    public function getDynoVersion(): string
    {
        return \dyno\VERSION;
    }

    /**
     * @param string $variable
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getConfig($variable, $defaultValue = null)
    {
        if ($this->properties->exists($variable)) {
            return $this->properties->get($variable);
        }
        return $defaultValue;
    }

    /**
     * @return BaseLang
     */
    public function getLanguage(): BaseLang
    {
        return $this->baseLang;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "Dyno";
    }

    /**
     * @return string
     */
    public function getCodename(): string
    {
        return \dyno\CODENAME;
    }

    /**
     * @return string
     */
    public function getApiVersion(): string
    {
        return \dyno\API_VERSION;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->getConfig("server-ip", "0.0.0.0");
    }

    /**
     * @return int
     */
    public function getDynoPort(): int
    {
        return (int)$this->getConfig("dyno-port", 10102);
    }

    public function enablePlugins()
    {
        foreach ($this->pluginManager->getPlugins() as $plugin) {
            if (!$plugin->isEnabled()) {
                $this->enablePlugin($plugin);
            }
        }
    }

    /**
     * @param Plugin $plugin
     */
    public function enablePlugin(Plugin $plugin)
    {
        $this->pluginManager->enablePlugin($plugin);
    }

    /**
     * @return MainLogger
     */
    public function getLogger(): MainLogger
    {
        return $this->logger;
    }

    public function shutdown()
    {
        if ($this->isRunning) {
            $this->isRunning = false;
        }
    }

    /**
     * @return ServerScheduler
     */
    public function getScheduler(): ServerScheduler
    {
        return $this->scheduler;
    }

    /**
     * @return Config
     */
    public function getProperties(): Config
    {
        return $this->properties;
    }

    /**
     * @throws \Throwable
     */
    public function start()
    {
        $this->tickCounter = 0;

        if (function_exists("pcntl_signal")) {
            pcntl_signal(SIGTERM, [$this, "handleSignal"]);
            pcntl_signal(SIGINT, [$this, "handleSignal"]);
            pcntl_signal(SIGHUP, [$this, "handleSignal"]);
            $this->dispatchSignals = true;
        }

        $this->logger->info(TextFormat::GREEN . $this->getLanguage()->translateString("dyno.server.startFinished", [round(microtime(true) - \dyno\START_TIME, 3)]));

        if (!file_exists(($path = $this->getPluginPath() . DIRECTORY_SEPARATOR . "Dyno"))) {
            @mkdir($path);
        }

        if (!file_exists(($path = $this->getDataPath() . DIRECTORY_SEPARATOR . "bases"))) {
            @mkdir($path);
        }

        $this->tickProcessor();
        $this->forceShutdown();

        gc_collect_cycles();
    }

    /**
     * @return string
     */
    public function getPluginPath(): string
    {
        return $this->pluginPath;
    }

    /**
     * @return string
     */
    public function getDataPath(): string
    {
        return $this->dataPath;
    }

    /**
     * @throws \Throwable
     */
    private function tickProcessor()
    {
        $this->nextTick = microtime(true);
        while ($this->isRunning) {
            $this->tick();
            $next = $this->nextTick - 0.0001;
            if ($next > microtime(true)) {
                try {
                    @time_sleep_until($next);
                } catch (\Throwable $e) {
                }
            }
        }
    }

    /**
     * @return bool
     * @throws \Throwable
     */
    private function tick()
    {
        $tickTime = microtime(true);
        if (($tickTime - $this->nextTick) < -0.025) { //Allow half a tick of diff
            return false;
        }
        ++$this->tickCounter;
        $this->checkConsole();
        $this->dynoInterface->process();
        $this->scheduler->mainThreadHeartbeat($this->tickCounter);
        foreach ($this->clients as $client) {
            $client->onUpdate();
        }
        if (($this->tickCounter % 200) == 0) {//re-generate client data
            $this->updateClientData();
        }
        if (($this->tickCounter & 0b1111) === 0) {
            $this->titleTick();
            $this->maxTick = 100;
            $this->maxUse = 0;
        }
        if ($this->dispatchSignals and $this->tickCounter % 5 === 0) {
            pcntl_signal_dispatch();
        }
        $now = microtime(true);
        $tick = min(100, 1 / max(0.001, $now - $tickTime));
        $use = min(1, ($now - $tickTime) / 0.05);
        if ($this->maxTick > $tick) {
            $this->maxTick = $tick;
        }
        if ($this->maxUse < $use) {
            $this->maxUse = $use;
        }
        array_shift($this->tickAverage);
        $this->tickAverage[] = $tick;
        array_shift($this->useAverage);
        $this->useAverage[] = $use;
        if (($this->nextTick - $tickTime) < -1) {
            $this->nextTick = $tickTime;
        } else {
            $this->nextTick += 0.01;
        }
        //printf("Tick time ".microtime(true)."\n");
        return true;
    }

    /**
     * @throws \Throwable
     */
    public function checkConsole()
    {
        if (($line = $this->console->getLine()) !== null) {
            $this->dispatchCommand($this->consoleSender, $line);
        }
    }

    /**
     * Executes a command from a CommandSender
     *
     * @param CommandSender $sender
     * @param string $commandLine
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function dispatchCommand(CommandSender $sender, $commandLine)
    {
        if (!($sender instanceof CommandSender)) {
            throw new ServerException("CommandSender is not valid");
        }

        if ($this->commandMap->dispatch($sender, $commandLine)) {
            return true;
        }

        $sender->sendMessage(new TranslationContainer(TextFormat::RED . "%dyno.commands.generic.notFound"));

        return false;
    }

    public function updateClientData()
    {
        if (count($this->clients) > 0) {
            $this->clientData = [];
            $this->clientData["clientList"] = "";
            foreach ($this->clients as $client) {
                $this->clientData[$client->getHash()] = [
                    "ip" => $client->getIp(),
                    "port" => $client->getPort(),
                    "description" => $client->getDescription(),
                    "tps" => $client->getTps(),
                    "load" => $client->getLoad(),
                    "upTime" => $client->getUpTime()
                ];
            }
            $this->clientData = json_encode($this->clientData);
        }
    }

    private function titleTick()
    {
        if (!Terminal::hasFormattingCodes()) {
            return;
        }

        $d = Utils::getRealMemoryUsage();

        $u = Utils::getMemoryUsage(true);
        $usage = round(($u[0] / 1024) / 1024, 2) . "/" . round(($d[0] / 1024) / 1024, 2) . "/" . round(($u[1] / 1024) / 1024, 2) . "/" . round(($u[2] / 1024) / 1024, 2) . " MB @ " . Utils::getThreadCount() . " threads";

        echo "\x1b]0;" . $this->getName() . " " .
            $this->getVersionString()->getRelease() .
            " | Memory " . $usage .
            " kB/s | TPS " . $this->getTicksPerSecondAverage() .
            " | Load " . $this->getTickUsageAverage() . "%\x07";

    }

    /**
     * @return VersionString
     */
    public function getVersionString(): VersionString
    {
        return $this->version;
    }

    /**
     * Returns the last server TPS average measure
     * @return float
     */
    public function getTicksPerSecondAverage(): float
    {
        return round(array_sum($this->tickAverage) / count($this->tickAverage), 2);
    }

    /**
     * Returns the TPS usage/load average in %
     * @return float
     */
    public function getTickUsageAverage(): float
    {
        return round((array_sum($this->useAverage) / count($this->useAverage)) * 100, 2);
    }

    public function forceShutdown()
    {
        if ($this->hasStopped) {
            return;
        }
        try {
            $this->hasStopped = true;
            $this->shutdown();
            if ($this->database !== null) {
                $this->database->close();
            }
            foreach ($this->clients as $client) {
                $client->close("Dyno server closed");
            }

            $this->getLogger()->debug("Disabling all plugins");
            $this->pluginManager->disablePlugins();


            $this->getLogger()->debug("Removing event handlers");
            HandlerList::unregisterAll();

            $this->getLogger()->debug("Stopping all tasks");
            $this->scheduler->cancelAllTasks();
            $this->scheduler->mainThreadHeartbeat(PHP_INT_MAX);

            $this->getLogger()->debug("Saving properties");
            $this->properties->save();

            $this->getLogger()->debug("Closing console");
            $this->console->shutdown();
            $this->console->notify();
            //$this->memoryManager->doObjectCleanup();

            gc_collect_cycles();
        } catch (\Throwable $e) {
            $this->logger->logException($e);
            $this->logger->emergency("Crashed while crashing, killing process");
            @kill(getmypid());
        }
    }

    /**
     * @param \Throwable $e
     * @param null $trace
     */
    public function exceptionHandler(\Throwable $e, $trace = null)
    {
        if ($e === null) {
            return;
        }

        global $lastError;

        if ($trace === null) {
            $trace = $e->getTrace();
        }

        $errstr = $e->getMessage();
        $errfile = $e->getFile();
        $errno = $e->getCode();
        $errline = $e->getLine();

        $type = ($errno === E_ERROR or $errno === E_USER_ERROR) ? \LogLevel::ERROR : (($errno === E_USER_WARNING or $errno === E_WARNING) ? \LogLevel::WARNING : \LogLevel::NOTICE);
        if (($pos = strpos($errstr, "\n")) !== false) {
            $errstr = substr($errstr, 0, $pos);
        }

        $errfile = cleanPath($errfile);

        if ($this->logger instanceof MainLogger) {
            $this->logger->logException($e, $trace);
        }

        $lastError = [
            "type" => $type,
            "message" => $errstr,
            "fullFile" => $e->getFile(),
            "file" => $errfile,
            "line" => $errline,
            "trace" => @getTrace(1, $trace)
        ];

        global $lastExceptionError, $lastError;
        $lastExceptionError = $lastError;
        //$this->crashDump();
    }

    /**
     * @return Server
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @return int
     */
    public static function getServerId(): int
    {
        return self::$serverId;
    }

    /**
     * @return MysqlDatabase|null
     */
    public function getDataBase(): ?MysqlDatabase
    {
        return $this->database;
    }

    /**
     * @param MysqlDatabase $database
     */
    public function setDatabase(MysqlDatabase $database)
    {
        if (isset($this->database)) {
            throw new \InvalidStateException($this->getLanguage()->translate(
                new TranslationContainer("%dyno.mysql.connection.alreadySet")
            ));
        }
        $this->database = $database;
        $this->getLogger()->info(TextFormat::GREEN . $this->getLanguage()->translate(
                new TranslationContainer("%dyno.mysql.connection.successful"))
        );
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return \dyno\VERSION;
    }

    /**
     * @return PluginManager
     */
    public function getPluginManager(): PluginManager
    {
        return $this->pluginManager;
    }

    /**
     * @param Client $client
     */
    public function addClient(Client $client)
    {
        $this->clients[$client->getHash()] = $client;
    }

    /**
     * @param Client $client
     */
    public function removeClient(Client $client)
    {
        if (isset($this->clients[$client->getHash()])) {
            unset($this->clients[$client->getHash()]);
        }
    }

    /**
     * @return Client[]
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    /**
     * @param string $pass
     * @return bool
     */
    public function comparePassword(string $pass): bool
    {
        return ($this->getConfig("password", Utils::random_password(16)) == $pass);
    }

    /**
     * @param TranslationContainer $message
     */
    public function displayTranslation(TranslationContainer $message)
    {
        $this->logger->info($this->getLanguage()->translateString($message->getText(), $message->getParameters()));
    }

    /**
     * @return DynoInterface
     */
    public function getInterface(): DynoInterface
    {
        return $this->dynoInterface;
    }

    /**
     * Returns the last server TPS measure
     * @return float
     */
    public function getTicksPerSecond(): float
    {
        return round($this->maxTick, 2);
    }

    /**
     * Returns the TPS usage/load in %
     * @return float
     */
    public function getTickUsage(): float
    {
        return round($this->maxUse * 100, 2);
    }

    public function getClientData()
    {
        return $this->clientData;
    }

    public function handleSignal($signo)
    {
        if ($signo === SIGTERM or $signo === SIGINT or $signo === SIGHUP) {
            $this->shutdown();
        }
    }

    /**
     * @param Plugin $plugin
     * @deprecated
     */
    public function loadPlugin(Plugin $plugin)
    {
        $this->enablePlugin($plugin);
    }

    public function disablePlugins()
    {
        $this->pluginManager->disablePlugins();
    }

    /**
     * @return SimpleCommandMap
     */
    public function getCommandMap(): SimpleCommandMap
    {
        return $this->commandMap;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return \ClassLoader
     */
    public function getLoader(): \ClassLoader
    {
        return $this->autoloader;
    }

    /**
     * @return Config
     */
    public function getMysqlProperties(): Config
    {
        return $this->mysqlProperties;
    }

    /**
     * @return Bases
     */
    public function getBases(): Bases
    {
        return $this->bases;
    }

    /**
     * @return Tables
     */
    public function getTables(): Tables
    {
        return $this->tables;
    }

    /**
     * @return bool
     */
    public function hasStrictMode(): bool
    {
        return $this->properties->get("strict-mode", true);
    }

    /**
     * @return bool
     */
    public function hasLogPacketReceived(): bool
    {
        return $this->properties->get("log-packet-received", false);
    }
}
