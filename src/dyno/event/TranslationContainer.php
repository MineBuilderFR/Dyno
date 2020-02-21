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

namespace dyno\event;

class TranslationContainer extends TextContainer
{

    /** @var string[] $params */
    protected $params = [];

    /**
     * @param string $text
     * @param string[] $params
     */
    public function __construct($text, array $params = [])
    {
        parent::__construct($text);

        $this->setParameters($params);
    }

    /**
     * @param string[] $params
     */
    public function setParameters(array $params)
    {
        $i = 0;
        foreach ($params as $str) {
            $this->params[$i] = (string)$str;

            ++$i;
        }
    }

    /**
     * @return string[]
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * @param int $i
     *
     * @return string
     */
    public function getParameter($i)
    {
        return isset($this->params[$i]) ? $this->params[$i] : null;
    }

    /**
     * @param int $i
     * @param string $str
     */
    public function setParameter($i, $str)
    {
        if ($i < 0 or $i > count($this->params)) { //Intended, allow to set the last
            throw new \InvalidArgumentException("Invalid index $i, have " . count($this->params));
        }

        $this->params[(int)$i] = $str;
    }
}