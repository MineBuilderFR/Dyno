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

class VersionString
{
    private $major;
    private $build;
    private $minor;
    private $development = false;

    public function __construct($version = \dyno\VERSION)
    {
        if (is_int($version)) {
            $this->minor = $version & 0x1F;
            $this->major = ($version >> 5) & 0x0F;
            $this->generation = ($version >> 9) & 0x0F;
        } else {
            $version = preg_split("/([A-Za-z]*)[ _\\-]?([0-9]*)\\.([0-9]*)\\.{0,1}([0-9]*)(dev|)(-[\\0-9]{1,}|)/", $version, -1, PREG_SPLIT_DELIM_CAPTURE);
            $this->generation = isset($version[2]) ? (int)$version[2] : 0; //0-15
            $this->major = isset($version[3]) ? (int)$version[3] : 0; //0-15
            $this->minor = isset($version[4]) ? (int)$version[4] : 0; //0-31
            $this->development = $version[5] === "dev" ? true : false;
            if ($version[6] !== "") {
                $this->build = intval(substr($version[6], 1));
            } else {
                $this->build = 0;
            }
        }
    }

    /**
     * @deprecated
     */
    public function getStage()
    {
        return "final";
    }

    public function getGeneration()
    {
        return $this->generation;
    }

    public function getMajor()
    {
        return $this->major;
    }

    public function getMinor()
    {
        return $this->minor;
    }

    public function isDev()
    {
        return $this->development === true;
    }

    public function __toString()
    {
        return $this->get();
    }

    public function get($build = false)
    {
        return $this->getRelease() . ($this->development === true ? "dev" : "") . (($this->build > 0 and $build === true) ? "-" . $this->build : "");
    }

    public function getRelease()
    {
        return $this->generation . "." . $this->major . ($this->minor > 0 ? "." . $this->minor : "");
    }

    public function compare($target, $diff = false)
    {
        if (($target instanceof VersionString) === false) {
            $target = new VersionString($target);
        }
        $number = $this->getNumber();
        $tNumber = $target->getNumber();
        if ($diff === true) {
            return $tNumber - $number;
        }
        if ($number > $tNumber) {
            return -1; //Target is older
        } elseif ($number < $tNumber) {
            return 1; //Target is newer
        } elseif ($target->getBuild() > $this->getBuild()) {
            return 1;
        } elseif ($target->getBuild() < $this->getBuild()) {
            return -1;
        } else {
            return 0; //Same version
        }
    }

    public function getNumber()
    {
        return (int)(($this->generation << 9) + ($this->major << 5) + $this->minor);
    }

    public function getBuild()
    {
        return $this->build;
    }
}