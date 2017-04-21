<?php

namespace Dogfood\Internal;

use Dogfood\Exception\RuntimeException;
use Dogfood\Exception\ValidationException;

/**
 * Manage document instances
 *
 * @package erayd/dogfood
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class ValueHelper extends BaseInstance
{
    /** @var mixed Reference to value location **/
    protected $value;

    /** @var mixed Value key **/
    protected $name;

    /** @var string Value type */
    protected $type = [];

    /** @var boolean Whether a value has been set **/
    protected $defined = true;

    /**
     * Create a new ValueHelper
     *
     * @param State $state
     * @param mixed $valueLocation
     * @param bool $defined
     */
    public function __construct(State $state, &$valueLocation, $key = null, bool $defined = true)
    {
        parent::__construct($state);
        $this->value = &$valueLocation;
        $this->key = $key;
        $this->defined = $defined;

        $this->type = $defined ? $this->detectTypes($this->value) : null;
    }

    /**
     * Get value key
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Detect value type
     *
     * @param mixed $value
     * @return array
     */
    private function detectTypes(&$value)
    {
        // set detected types, from most to least specific
        $types = [gettype($value)];
        switch ($types[0]) {
            case 'NULL':
                $types = ['null'];
                break;
            case 'integer':
                $types[] = 'number';
                break;
            case 'double':
                $types = ['number'];
                if (($value > PHP_INT_MAX || $value < PHP_INT_MIN) && floor($value) == $value) {
                    // Integers larger than PHP's internal integer type are parsed as a double, so
                    // add the integer type for them. Note that floating-point values with a zero
                    // fractional component (e.g. 3.0000) are *not* integers, and should not be
                    // treated as such. Unfortunately, there is no way to tell the difference once
                    // the number is outside the bounds of PHP's integer type, so we just assume
                    // that the number should be an int if the fractional component is zero or
                    // missing, as this is the most common use-case.
                    array_unshift($types, 'integer');
                }
                break;
        }
        if (is_scalar($value)) {
            $types[] = 'scalar';
        }

        // may not be resource or callable
        if (is_resource($value) || $value instanceof \Closure) {
            throw ValidationException::CALLABLE_OR_RESOURCE();
        }

        return $types;
    }

    /**
     * Get the most-specific type
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->type[0];
    }

    /**
     * Set the instance value
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->defined = true;
        $this->type = $this->detectTypes($this->value);
    }

    /**
     * Get the instance value
     *
     * @return mixed
     */
    public function &getValue()
    {
        if ($this->defined) {
            return $this->value;
        }

        return null;
    }

    /**
     * Check whether the instance is one of the provided types
     *
     * @param string[] $types
     * @return bool
     */
    public function isType(string ...$types) : bool
    {
        // if the value is undefined, then we can set it to any type, so return true
        if (!$this->defined) {
            return true;
        }

        // check types
        foreach ($types as $type) {
            if (in_array($type, $this->type)) {
                // direct type comparison
                return true;
            }
        }

        // no match
        return false;
    }

    /**
     * Assert that the instance must be one of the provided types
     *
     * @param string[] $types
     */
    public function assertType(string ...$types)
    {
        if (!call_user_func_array([$this, 'isType'], $types)) {
            throw ValidationException::TYPE_ASSERTION_FAILED($type, $this->type[0]);
        }
    }

    /**
     * Check whether the instance has been defined
     *
     * @return bool
     */
    public function isDefined() : bool
    {
        return $this->defined;
    }

    /**
     * Handle dynamic type checks
     *
     * @param string $methodName
     * @param array $args
     * @return bool
     */
    public function __call($methodName, $args) : bool
    {
        // type checks
        if (substr($methodName, 0, 2) == 'is') {
            $type = strtolower(substr($methodName, 2));
            return $this->isType($type);
        }

        // type conversions
        if (substr($methodName, 0, 6) == 'assert') {
            $type = strtolower(substr($methodName, 6));
            $this->assertType($type);
            return true;
        }

        throw RuntimeException::UNKNOWN_METHOD(self::class, $methodName);
    }

    /**
     * Run a callback against each member
     *
     * @param callable $callback (mixed &$value, string $propertyName)
     */
    public function each(callable $callback)
    {
        $this->assertType('object', 'array');

        foreach ($this->value as $key => &$value) {
            if ($this->isArray()) {
                $callback($this->value[$key], $key);
            } else {
                $callback($this->value->$key, $key);
            }
        }
    }

    /**
     * Check whether a member exists
     *
     * @param mixed $member
     * @return bool
     */
    public function hasMember($member) : bool
    {
        $this->assertType('object', 'array');

        if ($this->isArray()) {
            return array_key_exists($member, $this->value);
        }

        return property_exists($this->value, $member);
    }

    /**
     * Create a new ValueHelper instance for a specified member
     *
     * @param State $state
     * @param array|object $target
     * @param mixed $key
     * @return self
     */
    public static function createForMember(State $state, $target, $key) : self
    {
        if (is_array($target)) {
            $defined = array_key_exists($key, $target);
            $helper = new self($state, $target[$key], $key, $defined);
            if (!$defined) {
                unset($target[$key]);
            }
        } elseif (is_object($target)) {
            $defined = property_exists($target, $key);
            $helper = new self($state, $target->$key, $key, $defined);
            if (!$defined) {
                unset($target->$key);
            }
        } else {
            throw RuntimeException::INVALID_TYPE(gettype($target));
        }

        return $helper;
    }

    /**
     * Get value as a regular expression
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
