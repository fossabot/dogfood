<?php

namespace JsonValidator;

/**
 * Exception handler
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class Exception extends \Exception
{
    // error codes
    const UNKNOWN_ERROR_CODE = 1;
    const UNDEFINED_ERROR = 2;
    const FETCH_ERROR = 3;
    const BOOLEAN_SCHEMA_FALSE = 4;
    const NULL_SCHEMA_DEFINITION = 5;
    const SCHEMA_ALREADY_REGISTERED = 6;
    const UNKNOWN_SCHEMA = 7;
    const INVALID_POINTER_TARGET = 8;
    const INVALID_IDENTIFIER = 9;
    const ADDITIONAL_ITEMS_PROHIBITED = 10;
    const INVALID_TYPE = 11;
    const ADDITIONAL_PROPERTIES_PROHIBITED = 12;
    const STRING_TOO_SHORT = 13;
    const STRING_TOO_LONG = 14;
    const SIMPLE_DEPENDENCIES_NOT_ALLOWED = 15;
    const REQUIRED_PROPERTY_MISSING = 16;
    const NOT_MULTIPLE_OF = 17;
    const NOT_IN_ENUM = 18;
    const NUMBER_LESS_THAN_EX_MINIMUM = 19;
    const NUMBER_LESS_THAN_MINIMUM = 20;
    const NUMBER_GREATER_THAN_EX_MAXIMUM = 21;
    const NUMBER_GREATER_THAN_MAXIMUM = 22;
    const TOO_MANY_ITEMS = 23;
    const NOT_ENOUGH_ITEMS = 24;
    const PATTERN_MISMATCH = 25;
    const REMOTE_FETCH_DISABLED = 26;
    const NON_UNIQUE_ARRAY = 27;
    const INVALID_IPV4 = 28;
    const INVALID_IPV6 = 29;
    const HOSTNAME_TOO_LONG = 30;
    const HOSTNAME_COMPONENT_TOO_LONG = 31;
    const INVALID_HOSTNAME_COMPONENT = 32;
    const INVALID_EMAIL = 33;
    const INVALID_REGEX = 34;
    const UNSUPPORTED_LOOKBEHIND = 35;
    const UNSUPPORTED_Z_ANCHOR = 36;
    const INVALID_DATETIME = 37;
    const INVALID_DATE = 38;
    const INVALID_TIME = 39;
    const INVALID_CSS_COLOR = 40;
    const INVALID_UTC_MILLISEC = 41;
    const INVALID_CSS_STYLE = 42;
    const INVALID_URI = 43;
    const INVALID_URI_REF = 44;
    const JSON_DECODE_ERROR = 45;
    const ANY_OF_FAIL = 46;
    const TOO_MANY_PROPERTIES = 47;
    const NOT_ENOUGH_PROPERTIES = 48;
    const NOT_IS_VALID = 49;
    const ONE_OF_NO_MATCHES = 50;
    const ONE_OF_TOO_MANY_MATCHES = 51;

    /**
     * Get message template
     *
     * @param int $code
     * @return string
     */
    public static function getTemplate(int $code) : string
    {
        switch ($code) {
            case self::UNKNOWN_ERROR_CODE:
                return 'Unknown error code: %s';
            case self::UNDEFINED_ERROR:
                return 'Undefined error: %s';
            case self::FETCH_ERROR:
                return 'Unable to fetch remote resource (%s): %s';
            case self::BOOLEAN_SCHEMA_FALSE:
                return 'A boolean schema of "false" always fails vaildation';
            case self::NULL_SCHEMA_DEFINITION:
                return 'No schema definition was provided';
            case self::SCHEMA_ALREADY_REGISTERED:
                return 'Another schema is already registered for URI: %s';
            case self::UNKNOWN_SCHEMA:
                return 'Unknown schema: %s';
            case self::INVALID_POINTER_TARGET:
                return 'Invalid pointer target: %s';
            case self::INVALID_IDENTIFIER:
                return 'Invalid ID: %s';
            case self::ADDITIONAL_ITEMS_PROHIBITED:
                return 'Additional items prohibited';
            case self::INVALID_TYPE:
                return 'Invalid type: %s';
            case self::ADDITIONAL_PROPERTIES_PROHIBITED:
                return 'Additional properties prohibited';
            case self::STRING_TOO_SHORT:
                return 'string must be at least %d characters long, but is only %d';
            case self::STRING_TOO_LONG:
                return 'string must be at most %d characters long, but is actually %d';
            case self::SIMPLE_DEPENDENCIES_NOT_ALLOWED:
                return 'Simple dependencies are not allowed';
            case self::REQUIRED_PROPERTY_MISSING:
                return 'Required property "%s" is missing';
            case self::NOT_MULTIPLE_OF:
                return '%s is not a multiple of %s';
            case self::NOT_IN_ENUM:
                return 'Not in enum';
            case self::NUMBER_LESS_THAN_EX_MINIMUM:
                return 'Number must be greater than %s';
            case self::NUMBER_LESS_THAN_MINIMUM:
                return 'Number must be at least %s';
            case self::NUMBER_GREATER_THAN_EX_MAXIMUM:
                return 'Number must be less than %s';
            case self::NUMBER_GREATER_THAN_MAXIMUM:
                return 'Number must be no greater than %s';
            case self::TOO_MANY_ITEMS:
                return 'Array must have no more than %s items, but contains %s';
            case self::NOT_ENOUGH_ITEMS:
                return 'Array must have at least %s items, but only contains %s';
            case self::PATTERN_MISMATCH:
                return 'String does not match pattern: %s';
            case self::REMOTE_FETCH_DISABLED:
                return 'Remote fetch is disabled - cannot fetch URI: %s';
            case self::NON_UNIQUE_ARRAY:
                return 'Array must be unique';
            case self::INVALID_IPV4:
                return 'Invalid IPv4 address';
            case self::INVALID_IPV6:
                return 'Invalid IPv6 address';
            case self::HOSTNAME_TOO_LONG:
                return 'Hostname is too long: %s';
            case self::HOSTNAME_COMPONENT_TOO_LONG:
                return 'Hostname component is too long: %s';
            case self::INVALID_HOSTNAME_COMPONENT:
                return 'Invalid hostname component: %s';
            case self::INVALID_EMAIL:
                return 'Invalid email address: %s';
            case self::INVALID_REGEX:
                return 'Invalid regular expression: %s';
            case self::UNSUPPORTED_LOOKBEHIND:
                return 'Unsupported lookbehind: %s';
            case self::UNSUPPORTED_Z_ANCHOR:
                return 'Unsupported \\Z anchor: %s';
            case self::INVALID_DATETIME:
                return 'Invalid date-time: %s';
            case self::INVALID_DATE:
                return 'Invalid date: %s';
            case self::INVALID_TIME:
                return 'Invalid time: %s';
            case self::INVALID_CSS_COLOR:
                return 'Invalid CSS colour: %s';
            case self::INVALID_UTC_MILLISEC:
                return 'Invalid utc-millisec: %s';
            case self::INVALID_CSS_STYLE:
                return 'Invalid CSS style: %s';
            case self::INVALID_URI:
                return 'Invalid URI: %s';
            case self::INVALID_URI_REF:
                return 'Invalid URI reference: %s';
            case self::JSON_DECODE_ERROR:
                return 'JSON decoding error: %s';
            case self::ANY_OF_FAIL:
                return 'Must be valid against at least one schema listed in "anyOf"';
            case self::TOO_MANY_PROPERTIES:
                return 'Object may have at most %s properties, but actually has %s';
            case self::NOT_ENOUGH_PROPERTIES:
                return 'Object must have at least %s properties, but only has %s';
            case self::NOT_IS_VALID:
                return 'Document is valid against "not", but should be invalid';
            case self::ONE_OF_NO_MATCHES:
                return 'Not valid against any "oneOf" schema';
            case self::ONE_OF_TOO_MANY_MATCHES:
                return 'Valid against more than one "oneOf" schema';

            default:
                throw self::UNDEFINED_ERROR($code);
        }
    }

    /**
     * Create a new exception for the given code
     *
     * @param string $codeName
     * @return self
     */
    public static function __callStatic($codeName, $params) : self
    {
        if (is_null($code = constant(self::class . "::$codeName"))) {
            $code = self::UNKNOWN_ERROR_CODE;
            $params = [$codeName];
        }

        $previous = end($params);
        if (is_object($previous) && $previous instanceof \Throwable) {
            array_pop($params);
        } else {
            $previous = null;
        }

        return new self(sprintf(self::getTemplate($code), ...$params), $code, $previous);
    }
}
