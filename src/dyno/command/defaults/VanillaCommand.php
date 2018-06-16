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

namespace dyno\command\defaults;

use dyno\command\{
    Command, CommandSender
};

abstract class VanillaCommand extends Command
{
    public const MAX_COORD = 30000000;
    public const MIN_COORD = -30000000;

    /**
     * VanillaCommand constructor.
     * @param string $name
     * @param string $description
     * @param null|string $usageMessage
     * @param array $aliases
     */
    public function __construct(string $name, string $description = "",
                                ?string $usageMessage = null, array $aliases = [])
    {
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    /**
     * @param CommandSender $sender
     * @param float $value
     * @param float $min
     * @param float $max
     * @return float|int
     */
    protected function getInteger(CommandSender $sender, float $value,
                                  float $min = self::MIN_COORD, float $max = self::MAX_COORD): float
    {
        $i = (double)$value;
        if ($i < $min) {
            $i = $min;
        } elseif ($i > $max) {
            $i = $max;
        }
        return $i;
    }

    /**
     * @param float $original
     * @param CommandSender $sender
     * @param $input
     * @param float $min
     * @param float $max
     * @return float|int
     */
    protected function getRelativeDouble(float $original, CommandSender $sender, $input,
                                         float $min = self::MIN_COORD, float $max = self::MAX_COORD)
    {
        if ($input{0} === "~") {
            $value = $this->getDouble($sender, substr($input, 1));
            return $original + $value;
        }
        return $this->getDouble($sender, $input, $min, $max);
    }

    /**
     * @param CommandSender $sender
     * @param float $value
     * @param float $min
     * @param float $max
     * @return float
     */
    protected function getDouble(CommandSender $sender, float $value,
                                 float $min = self::MIN_COORD, float $max = self::MAX_COORD): float
    {
        $i = (double)$value;
        if ($i < $min) {
            $i = $min;
        } elseif ($i > $max) {
            $i = $max;
        }
        return $i;
    }
}