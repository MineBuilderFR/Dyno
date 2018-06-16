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

namespace dyno\task;

class FileWriteTask extends AsyncTask
{

    /** @var string */
    private $path;
    /** @var string */
    private $contents;
    /** @var int */
    private $flags;

    /**
     * FileWriteTask constructor.
     * @param string $path
     * @param string $contents
     * @param int $flags
     */
    public function __construct(string $path, string $contents, int $flags = 0)
    {
        $this->path = $path;
        $this->contents = $contents;
        $this->flags = (int)$flags;
    }

    public function onRun()
    {
        try {
            file_put_contents($this->path, $this->contents, (int)$this->flags);
        } catch (\Throwable $e) {

        }
    }
}
