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

namespace dyno\exception\TableException;

use dyno\event\TranslationContainer;
use dyno\Server;
use Throwable;

/**
 * Class InvalidReturnValueException
 * @package dyno\exception\TableException
 *
 * When a type of value is returned when it is not the right type
 * Only called if strict_mode=on in properties
 */
class InvalidReturnValueException extends DynoTableException
{

    /**
     * InvalidReturnValueException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        if (!empty($message)) {
            $args = explode("|", $message);
            if (count($args) === 2) {
                $message = Server::getInstance()->getLanguage()->translate(
                    new TranslationContainer("%dyno.exception.table.invalidReturnValueException.message", [
                        $args[0], $args[1]
                    ])
                );
            }
        } else {
            $message = Server::getInstance()->getLanguage()->translate(
                new TranslationContainer("%dyno.exception.table.invalidReturnValueException.messageBase")
            );
        }
        parent::__construct($message, $code, $previous);
    }
}