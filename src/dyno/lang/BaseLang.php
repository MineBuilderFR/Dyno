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

namespace dyno\lang;

use dyno\event\{
    TextContainer, TranslationContainer
};

class BaseLang
{

    public const FALLBACK_LANGUAGE = "eng";

    /** @var string */
    protected $langName;

    /** @var array */
    protected $lang = [];
    /** @var array */
    protected $fallbackLang = [];

    public function __construct($lang, $path = null, $fallback = self::FALLBACK_LANGUAGE)
    {

        $this->langName = strtolower($lang);

        if ($path === null) {
            $path = \dyno\PATH . "src/dyno/lang/locale/";
        }

        $this->loadLang($path . $this->langName . ".ini", $this->lang);
        $this->loadLang($path . $fallback . ".ini", $this->fallbackLang);
    }

    protected function loadLang($path, array &$d)
    {
        if (file_exists($path) and strlen($content = file_get_contents($path)) > 0) {
            foreach (explode("\n", $content) as $line) {
                $line = trim($line);
                if ($line === "" or $line{0} === "#") {
                    continue;
                }

                $t = explode("=", $line);
                if (count($t) < 2) {
                    continue;
                }

                $key = trim(array_shift($t));
                $value = trim(implode("=", $t));

                if ($value === "") {
                    continue;
                }

                $d[$key] = $value;
            }
        }
    }

    public function getName(): string
    {
        return $this->get("language.name");
    }

    public function get($id)
    {
        if (isset($this->lang[$id])) {
            return $this->lang[$id];
        } elseif (isset($this->fallbackLang[$id])) {
            return $this->fallbackLang[$id];
        }

        return $id;
    }

    public function getLang()
    {
        return $this->langName;
    }

    /**
     * @param string $str
     * @param string[] $params
     *
     * @param null $onlyPrefix
     * @return string
     */
    public function translateString($str, array $params = [], $onlyPrefix = null)
    {
        $baseText = $this->get($str);
        $baseText = $this->parseTranslation(($baseText !== null and ($onlyPrefix === null or strpos($str, $onlyPrefix) === 0)) ? $baseText : $str, $onlyPrefix);

        foreach ($params as $i => $p) {
            $baseText = str_replace("{%$i}", $this->parseTranslation((string)$p), $baseText, $onlyPrefix);
        }

        return $baseText;
    }

    protected function parseTranslation($text, $onlyPrefix = null)
    {
        $newString = "";

        $replaceString = null;

        $len = strlen($text);
        for ($i = 0; $i < $len; ++$i) {
            $c = $text{$i};
            if ($replaceString !== null) {
                $ord = ord($c);
                if (
                    ($ord >= 0x30 and $ord <= 0x39) // 0-9
                    or ($ord >= 0x41 and $ord <= 0x5a) // A-Z
                    or ($ord >= 0x61 and $ord <= 0x7a) or // a-z
                    $c === "." or $c === "-"
                ) {
                    $replaceString .= $c;
                } else {
                    if (($t = $this->internalGet(substr($replaceString, 1))) !== null and ($onlyPrefix === null or strpos($replaceString, $onlyPrefix) === 1)) {
                        $newString .= $t;
                    } else {
                        $newString .= $replaceString;
                    }
                    $replaceString = null;

                    if ($c === "%") {
                        $replaceString = $c;
                    } else {
                        $newString .= $c;
                    }
                }
            } elseif ($c === "%") {
                $replaceString = $c;
            } else {
                $newString .= $c;
            }
        }

        if ($replaceString !== null) {
            if (($t = $this->internalGet(substr($replaceString, 1))) !== null and ($onlyPrefix === null or strpos($replaceString, $onlyPrefix) === 1)) {
                $newString .= $t;
            } else {
                $newString .= $replaceString;
            }
        }

        return $newString;
    }

    public function internalGet($id)
    {
        if (isset($this->lang[$id])) {
            return $this->lang[$id];
        } elseif (isset($this->fallbackLang[$id])) {
            return $this->fallbackLang[$id];
        }

        return null;
    }

    public function translate(TextContainer $c)
    {
        if ($c instanceof TranslationContainer) {
            $baseText = $this->internalGet($c->getText());
            $baseText = $this->parseTranslation($baseText !== null ? $baseText : $c->getText());

            foreach ($c->getParameters() as $i => $p) {
                $baseText = str_replace("{%$i}", $this->parseTranslation($p), $baseText);
            }
        } else {
            $baseText = $this->parseTranslation($c->getText());
        }

        return $baseText;
    }
}
