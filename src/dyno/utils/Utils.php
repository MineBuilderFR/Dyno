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

namespace dyno\utils;

use dyno\works\ThreadManager;

class Utils
{
    public static $online = true;
    public static $ip = false;
    public static $os;

    /**
     * GETs an URL using cURL
     *
     * @param       $page
     * @param int $timeout default 10
     * @param array $extraHeaders
     *
     * @return bool|mixed
     */
    public static function getURL($page, $timeout = 10, array $extraHeaders = [])
    {
        if (Utils::$online === false) {
            return false;
        }

        $ch = curl_init($page);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 Dyno"], $extraHeaders));
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int)$timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);
        $ret = curl_exec($ch);
        curl_close($ch);

        return $ret;
    }

    public static function getRealMemoryUsage()
    {
        $stack = 0;
        $heap = 0;

        if (Utils::getOS() === "linux" or Utils::getOS() === "android") {
            $mappings = file("/proc/self/maps");
            foreach ($mappings as $line) {
                if (preg_match("#([a-z0-9]+)\\-([a-z0-9]+) [rwxp\\-]{4} [a-z0-9]+ [^\\[]*\\[([a-zA-z0-9]+)\\]#", trim($line), $matches) > 0) {
                    if (strpos($matches[3], "heap") === 0) {
                        $heap += hexdec($matches[2]) - hexdec($matches[1]);
                    } elseif (strpos($matches[3], "stack") === 0) {
                        $stack += hexdec($matches[2]) - hexdec($matches[1]);
                    }
                }
            }
        }

        return [$heap, $stack];
    }

    /**
     * Returns the current Operating System
     * Windows => win
     * MacOS => mac
     * iOS => ios
     * Android => android
     * Linux => Linux
     * BSD => bsd
     * Other => other
     *
     * @param bool $recalculate
     * @return string
     */
    public static function getOS($recalculate = false)
    {
        if (self::$os === null or $recalculate) {
            $uname = php_uname("s");
            if (stripos($uname, "Darwin") !== false) {
                if (strpos(php_uname("m"), "iP") === 0) {
                    self::$os = "ios";
                } else {
                    self::$os = "mac";
                }
            } elseif (stripos($uname, "Win") !== false or $uname === "Msys") {
                self::$os = "win";
            } elseif (stripos($uname, "Linux") !== false) {
                if (@file_exists("/system/build.prop")) {
                    self::$os = "android";
                } else {
                    self::$os = "linux";
                }
            } elseif (stripos($uname, "BSD") !== false or $uname === "DragonFly") {
                self::$os = "bsd";
            } else {
                self::$os = "other";
            }
        }

        return self::$os;
    }

    public static function getMemoryUsage($advanced = false)
    {
        $reserved = memory_get_usage();
        $VmSize = null;
        $VmRSS = null;
        if (Utils::getOS() === "linux" or Utils::getOS() === "android") {
            $status = file_get_contents("/proc/self/status");
            if (preg_match("/VmRSS:[ \t]+([0-9]+) kB/", $status, $matches) > 0) {
                $VmRSS = $matches[1] * 1024;
            }

            if (preg_match("/VmSize:[ \t]+([0-9]+) kB/", $status, $matches) > 0) {
                $VmSize = $matches[1] * 1024;
            }
        }

        if ($VmRSS === null) {
            $VmRSS = memory_get_usage();
        }

        if (!$advanced) {
            return $VmRSS;
        }

        if ($VmSize === null) {
            $VmSize = memory_get_usage(true);
        }

        return [$reserved, $VmRSS, $VmSize];
    }

    public static function getThreadCount()
    {
        if (Utils::getOS() === "linux" or Utils::getOS() === "android") {
            if (preg_match("/Threads:[ \t]+([0-9]+)/", file_get_contents("/proc/self/status"), $matches) > 0) {
                return (int)$matches[1];
            }
        }

        return count(ThreadManager::getInstance()->getAll()) + 3; //RakLib + MainLogger + Main Thread
    }

    public static function getCoreCount($recalculate = false)
    {
        static $processors = 0;

        if ($processors > 0 and !$recalculate) {
            return $processors;
        } else {
            $processors = 0;
        }

        switch (Utils::getOS()) {
            case "linux":
            case "android":
                if (file_exists("/proc/cpuinfo")) {
                    foreach (file("/proc/cpuinfo") as $l) {
                        if (preg_match('/^processor[ \t]*:[ \t]*[0-9]+$/m', $l) > 0) {
                            ++$processors;
                        }
                    }
                } else {
                    if (preg_match("/^([0-9]+)\\-([0-9]+)$/", trim(@file_get_contents("/sys/devices/system/cpu/present")), $matches) > 0) {
                        $processors = (int)($matches[2] - $matches[1]);
                    }
                }
                break;
            case "bsd":
            case "mac":
                $processors = (int)`sysctl -n hw.ncpu`;
                $processors = (int)`sysctl -n hw.ncpu`;
                break;
            case "win":
                $processors = (int)getenv("NUMBER_OF_PROCESSORS");
                break;
        }
        return $processors;
    }

    /**
     * Returns a string that can be printed, replaces non-printable characters
     *
     * @param $str
     *
     * @return string
     */
    public static function printable($str)
    {
        if (!is_string($str)) {
            return gettype($str);
        }

        return preg_replace('#([^\x20-\x7E])#', '.', $str);
    }

    /**
     * This function tries to get all the entropy available in PHP, and distills it to get a good RNG.
     *
     *
     * @param int $length default 16, Number of bytes to generate
     * @param bool $secure default true, Generate secure distilled bytes, slower
     * @param bool $raw default true, returns a binary string if true, or an hexadecimal one
     * @param string $startEntropy default null, adds more initial entropy
     * @param int &$rounds Will be set to the number of rounds taken
     * @param int &$drop Will be set to the amount of dropped bytes
     *
     * @return string
     */
    public static function getRandomBytes($length = 16, $secure = true, $raw = true, $startEntropy = "", &$rounds = 0, &$drop = 0)
    {
        static $lastRandom = "";
        $output = "";
        $length = abs((int)$length);
        $secureValue = "";
        $rounds = 0;
        $drop = 0;
        while (!isset($output{$length - 1})) {
            //some entropy, but works ^^
            $weakEntropy = [
                is_array($startEntropy) ? implode($startEntropy) : $startEntropy,
                __DIR__,
                PHP_OS,
                microtime(),
                (string)lcg_value(),
                (string)PHP_MAXPATHLEN,
                PHP_SAPI,
                (string)PHP_INT_MAX . "." . PHP_INT_SIZE,
                serialize($_SERVER),
                get_current_user(),
                (string)memory_get_usage() . "." . memory_get_peak_usage(),
                php_uname(),
                phpversion(),
                zend_version(),
                (string)getmypid(),
                (string)getmyuid(),
                (string)mt_rand(),
                (string)getmyinode(),
                (string)getmygid(),
                (string)rand(),
                function_exists("zend_thread_id") ? ((string)zend_thread_id()) : microtime(),
                function_exists("getrusage") ? implode(getrusage()) : microtime(),
                function_exists("sys_getloadavg") ? implode(sys_getloadavg()) : microtime(),
                serialize(get_loaded_extensions()),
                sys_get_temp_dir(),
                (string)disk_free_space("."),
                (string)disk_total_space("."),
                uniqid(microtime(), true),
                file_exists("/proc/cpuinfo") ? file_get_contents("/proc/cpuinfo") : microtime(),
            ];

            shuffle($weakEntropy);
            $value = hash("sha512", implode($weakEntropy), true);
            $lastRandom .= $value;
            foreach ($weakEntropy as $k => $c) { //mixing entropy values with XOR and hash randomness extractor
                $value ^= hash("sha256", $c . microtime() . $k, true) . hash("sha256", mt_rand() . microtime() . $k . $c, true);
                $value ^= hash("sha512", ((string)lcg_value()) . $c . microtime() . $k, true);
            }
            unset($weakEntropy);

            if ($secure === true) {

                if (file_exists("/dev/urandom")) {
                    $fp = fopen("/dev/urandom", "rb");
                    $systemRandom = fread($fp, 64);
                    fclose($fp);
                } else {
                    $systemRandom = str_repeat("\x00", 64);
                }

                $strongEntropyValues = [
                    is_array($startEntropy) ? hash("sha512", $startEntropy[($rounds + $drop) % count($startEntropy)], true) : hash("sha512", $startEntropy, true), //Get a random index of the startEntropy, or just read it
                    $systemRandom,
                    function_exists("openssl_random_pseudo_bytes") ? openssl_random_pseudo_bytes(64) : str_repeat("\x00", 64),
                    function_exists("mcrypt_create_iv") ? mcrypt_create_iv(64, MCRYPT_DEV_URANDOM) : str_repeat("\x00", 64),
                    $value,
                ];
                $strongEntropy = array_pop($strongEntropyValues);
                foreach ($strongEntropyValues as $value) {
                    $strongEntropy = $strongEntropy ^ $value;
                }
                $value = "";
                //Von Neumann randomness extractor, increases entropy
                $bitcnt = 0;
                for ($j = 0; $j < 64; ++$j) {
                    $a = ord($strongEntropy{$j});
                    for ($i = 0; $i < 8; $i += 2) {
                        $b = ($a & (1 << $i)) > 0 ? 1 : 0;
                        if ($b != (($a & (1 << ($i + 1))) > 0 ? 1 : 0)) {
                            $secureValue |= $b << $bitcnt;
                            if ($bitcnt == 7) {
                                $value .= chr($secureValue);
                                $secureValue = 0;
                                $bitcnt = 0;
                            } else {
                                ++$bitcnt;
                            }
                            ++$drop;
                        } else {
                            $drop += 2;
                        }
                    }
                }
            }
            $output .= substr($value, 0, min($length - strlen($output), $length));
            unset($value);
            ++$rounds;
        }
        $lastRandom = hash("sha512", $lastRandom, true);

        return $raw === false ? bin2hex($output) : $output;
    }

    /**
     * @param int $passlenght
     * @return string
     */
    public static function random_password(int $passlenght)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $password = array();
        $alpha_length = strlen($alphabet) - 1;
        for ($i = 0; $i <= $passlenght; $i++) {
            $n = rand(0, $alpha_length);
            $password[] = $alphabet[$n];
        }
        return implode($password);
    }
}
