<?php

namespace JsonValidator\Internal;

use Sabre\Uri;

/**
 * Various useful utility methods
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
abstract class Util
{
    /**
     * Clamp a schema URI
     *
     * @param string $uri
     * @param string $base
     * @return string
     */
    public static function clampURI(string $uri, string $base = null) : string
    {
        // trim empty fragment
        $uri = rtrim($uri, '#');

        // resolve against base
        if (!is_null($base)) {
            $uri = Uri\resolve($base, $uri);
        }

        return $uri;
    }

    /**
     * Decode a JSON pointer
     *
     * @param string $pointer
     * @return array
     */
    public static function decodePointer(string $pointer) : array
    {
        $parts = array_map(function ($item) {
            return strtr($item, ['~1' => '/', '~0' => '~']);
        }, explode('/', ltrim(rawurldecode($pointer), '#')));

        while (count($parts) && !strlen($parts[0])) {
            array_shift($parts);
        }

        return $parts;
    }

    /**
     * Encode a JSON pointer
     *
     * @param array $pointer
     * @return string
     */
    public static function encodePointer($pointer) : string
    {
        return '#/' . implode('/', array_map(function ($item) {
            return strtr($item, ['~' => '~0', '/' => '~1', '%' => '%25']);
        }, $pointer));
    }

    /**
     * Get ECMA-262 pattern as a regular expression
     *
     * @param string $pattern
     * @return string
     */
    public static function patternToPCRE(string $pattern) : string
    {
        // fix weird wildcard syntax
        $pattern = str_replace('[^]', '(?:.|\s)', $pattern);

        // escape delimiters & add wrapping
        $pattern = '/' . str_replace('/', '\/', $pattern) . '/u';

        return $pattern;
    }
}
