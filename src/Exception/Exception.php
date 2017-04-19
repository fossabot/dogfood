<?php

namespace Dogfood\Exception;

/**
 * @package erayd/dogfood
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
abstract class Exception extends \Exception
{
    const CUSTOM_ERROR                      = 0;
    const UNKNOWN_ERROR                     = 1;
    const FETCH                             = 2;
    const JSON_DECODE                       = 3;
    const FETCH_SCHEME                      = 4;
    const PROPERTY_NOT_SET                  = 5;
    const SCHEMA_REGISTER_ONCE_ONLY         = 6;
    const SCHEMA_NOT_REGISTERED             = 7;
    const REF_BASE_NOT_SET                  = 8;
    const REF_TARGET_UNDEFINED              = 9;
    const REF_TARGET_INVALID_TYPE           = 10;
    const INVALID_ID_FORMAT                 = 11;
    const INVALID_POINTER_FORMAT            = 12;
    const INVALID_POINTER_TARGET            = 13;
    const MISSING_HANDLER                   = 14;
    const CALLABLE_OR_RESOURCE              = 15;
    const UNKNOWN_METHOD                    = 16;
    const TYPE_ASSERTION_FAILED             = 17;
    const ADDITIONAL_ITEMS_FORBIDDEN        = 18;
    const ADDITIONAL_PROPERTIES_FORBIDDEN   = 19;
    const INVALID_TYPE                      = 20;
    const STRING_SHORTER_THAN_MIN_LENGTH    = 21;
    const NO_SIMPLE_DEPENDENCIES            = 22;
    const MISSING_DEPENDENCY                = 23;
    const NON_STRING_ARRAY_DEPENDENCY       = 24;
    const IS_ILLEGAL_TYPE                   = 25;
    const NOT_MULTIPLE_OF                   = 26;
    const NOT_IN_ENUM                       = 27;
    const MISSING_REQUIRED                  = 28;
    const NUMBER_LOWER_THAN_MINIMUM         = 29;
    const NUMBER_GREATER_THAN_MAXIMUM       = 30;
    const MORE_ITEMS_THAN_MAX               = 31;
    const STRING_LONGER_THAN_MAX_LENGTH     = 32;
    const FEWER_ITEMS_THAN_MIN              = 33;
    const STRING_DOES_NOT_MATCH_PATTERN     = 34;
    const NON_UNIQUE_ARRAY                  = 35;
    const ANY_OF_NO_MATCH                   = 36;
    const ONE_OF_NO_OR_EXTRA_MATCH          = 37;
    const MORE_PROPERTIES_THAN_MAX          = 38;
    const FEWER_PROPERTIES_THAN_MIN         = 39;
    const MATCHED_NOT                       = 40;
    const SCHEMA_IMPORT_ERROR               = 41;

    /**
     * Create a new Exception
     *
     * @api
     *
     * @param int $code
     * @param array $args
     */
    public function __construct(int $code, ...$args)
    {
        if ($code != self::CUSTOM_ERROR) {
            array_unshift($args, self::getTemplate($code));
        }

        $previous = end($args) instanceof \Throwable ? array_pop($args) : null;

        parent::__construct(call_user_func_array('sprintf', $args), $code, $previous);
    }

    /**
     * Create a new Exception
     *
     * @api
     *
     * @param string $errorName
     * @param array $args
     * @return self
     */
    public static function __callStatic(string $errorName, $args) : self
    {
        $className = get_called_class();
        $const = "$className::$errorName";
        if (defined($const)) {
            array_unshift($args, constant($const));
        } else {
            array_unshift($args, self::UNKNOWN_ERROR, $errorName);
        }

        return new $className(...$args);
    }

    /**
     * Get message template
     *
     * @api
     *
     * @param int $code
     * @return string
     */
    public static function getTemplate(int $code) : string
    {
        switch ($code) {
            case self::UNKNOWN_ERROR:
                return 'Unknown error: %s';
            case self::FETCH:
                return 'Error fetching content from URI: %s';
            case self::JSON_DECODE:
                return 'JSON decode error: %s';
            case self::FETCH_SCHEME:
                return 'Fetching remote resources with scheme "%s" is not supported (%s)';
            case self::PROPERTY_NOT_SET:
                return 'Property "%s" is not set';
            case self::SCHEMA_REGISTER_ONCE_ONLY:
                return 'Schema URIs may only be registered once, and "%s" is already registered';
            case self::SCHEMA_NOT_REGISTERED:
                return 'Schema "%s" is not registered';
            case self::REF_BASE_NOT_SET:
                return 'Resolution base for "$ref" is not set';
            case self::REF_TARGET_UNDEFINED:
                return 'The target for reference "%s" is not defined';
            case self::REF_TARGET_INVALID_TYPE:
                return 'Encountered invalid type "%s" while resolving URI reference: %s';
            case self::INVALID_ID_FORMAT:
                return 'Invalid ID format: %s';
            case self::INVALID_POINTER_FORMAT:
                return 'Invalid format for JSON pointer: %s';
            case self::INVALID_POINTER_TARGET:
                return 'Invalid pointer target: %s';
            case self::MISSING_HANDLER:
                return 'No handler available for keyword: %s';
            case self::CALLABLE_OR_RESOURCE:
                return 'JSON documents may not contain "callable" or "resource" types';
            case self::UNKNOWN_METHOD:
                return 'Unknown method: %s::%s()';
            case self::TYPE_ASSERTION_FAILED:
                return 'A value of type "%s" is required, but type "%s" was provided';
            case self::ADDITIONAL_ITEMS_FORBIDDEN:
                return 'No additional items are allowed';
            case self::ADDITIONAL_PROPERTIES_FORBIDDEN:
                return 'No additional properties are allowed';
            case self::INVALID_TYPE:
                return 'Invalid type: %s';
            case self::STRING_SHORTER_THAN_MIN_LENGTH:
                return 'String must be at least %d characters long';
            case self::NO_SIMPLE_DEPENDENCIES:
                return 'Simple string dependencies are not allowed';
            case self::MISSING_DEPENDENCY:
                return 'Required dependency "%s" is missing';
            case self::NON_STRING_ARRAY_DEPENDENCY:
                return 'Array dependencies must be strings';
            case self::IS_ILLEGAL_TYPE:
                return 'Value is of an illegal type: %s';
            case self::NOT_MULTIPLE_OF:
                return '%s is not a multiple of %s';
            case self::NOT_IN_ENUM:
                return 'Not found in enum';
            case self::MISSING_REQUIRED:
                return 'Required item "%s" is missing';
            case self::NUMBER_LOWER_THAN_MINIMUM:
                return 'Number is lower than the allowed minimum';
            case self::NUMBER_GREATER_THAN_MAXIMUM:
                return 'Number is greater than the allowed maximum';
            case self::MORE_ITEMS_THAN_MAX:
                return 'Array must contain no more than %d items';
            case self::STRING_LONGER_THAN_MAX_LENGTH:
                return 'String must be no longer than %d characters';
            case self::FEWER_ITEMS_THAN_MIN:
                return 'Array must contain at least %d items';
            case self::STRING_DOES_NOT_MATCH_PATTERN:
                return 'String does not match pattern: %s';
            case self::NON_UNIQUE_ARRAY:
                return 'Array is not unique';
            case self::ANY_OF_NO_MATCH:
                return 'Must match at least one constrant schema';
            case self::ONE_OF_NO_OR_EXTRA_MATCH:
                return 'Must match exactly one constraint schema';
            case self::MORE_PROPERTIES_THAN_MAX:
                return 'Object must have no more than %d properties';
            case self::FEWER_PROPERTIES_THAN_MIN:
                return 'Object must have at least %d properties';
            case self::MATCHED_NOT:
                return 'Must not match "not" schema';
            case self::SCHEMA_IMPORT_ERROR:
                return 'Error importing schema (%s): %s';
            default:
                throw new self(self::UNKNOWN_ERROR);
        }
    }
}
