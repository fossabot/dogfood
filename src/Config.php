<?php

namespace JsonValidator;

/**
 * Container for config options
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
interface Config
{
    // whether to fetch remote resources
    // boolean [true]
    const FETCH_REMOTE = 1;

    // handler to use for retrieving remote resources
    // callable(string $uri) : string [file_get_contents]
    const FETCH_HANDLER = 2;

    // whether to set undefined values to their schema defaults
    // boolean [false]
    const APPLY_DEFAULTS = 3;

    // whether to throw exceptions rather than returning false on validation failure
    // boolean [true]
    const THROW_EXCEPTIONS = 4;

    // default config
    const DEFAULT_CONFIG = [
        self::FETCH_REMOTE => true,
        self::FETCH_HANDLER => 'file_get_contents',
        self::APPLY_DEFAULTS => false,
        self::THROW_EXCEPTIONS => false,
    ];
}
