<?php

namespace JsonValidator\Internal;

use JsonValidator\Exception;

/**
 * Helper for working with values
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class ValueHelper extends BaseInstance implements \Countable
{
    const VALUE_TYPE_SCALAR = 1;
    const VALUE_TYPE_ARRAY = 2;
    const VALUE_TYPE_OBJECT = 3;

    /** @var mixed Actual value to operate on */
    protected $value = [];

    /** @var SchemaInfo Schema spec to use */
    protected $spec = null;

    /** @var array Path to the current value */
    protected $path = [];

    /**
     * Create a new instance
     *
     * @param State $state
     * @param SchemaInfo $spec
     * @param mixed $target
     * @param mixed $member
     */
    public function __construct(State $state, SchemaInfo $spec, &$target, $member = null)
    {
        parent::__construct($state);

        $this->spec = $spec;

        $this->value = [
            is_null($member) ? self::VALUE_TYPE_SCALAR : (is_array($target) ? self::VALUE_TYPE_ARRAY : self::VALUE_TYPE_OBJECT),
            null,
            $member
        ];
        $this->value[1] = &$target;
    }

    /**
     * Dynamic bindings
     *
     * @param string $methodName
     * @param array $args
     */
    public function __call(string $methodName, array $args)
    {
        if (substr($methodName, 0, 2) == 'is') {
            return $this->isType(strtolower(substr($methodName, 2)), $args[0] ?? false);
        }
    }

    /**
     * Check whether the value is defined
     *
     * @return bool
     */
    public function isDefined() : bool
    {
        switch ($this->value[0]) {
            case self::VALUE_TYPE_SCALAR:
                return true; // impossible to tell whether a scalar value is defined or null, so assume true
            case self::VALUE_TYPE_ARRAY:
                return array_key_exists($this->value[2], $this->value[1]);
            case self::VALUE_TYPE_OBJECT:
                return property_exists($this->value[1], $this->value[2]);
        }
    }

    /**
     * Set the value
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        switch ($this->value[0]) {
            case self::VALUE_TYPE_SCALAR:
                $this->value[1] = $value;
                break;
            case self::VALUE_TYPE_ARRAY:
                $this->value[1][$this->value[2]] = $value;
                break;
            case self::VALUE_TYPE_OBJECT:
                $this->value[1]->{$this->value[2]} = $value;
                break;
        }
    }

    /**
     * Get the value
     *
     * @param mixed $default
     * @return mixed
     */
    public function &getValue(...$default)
    {
        if (!$this->isDefined()) {
            if (array_key_exists(0, $default)) {
                return $default[0];
            } else {
                $null = null;
                return $null;
            }
        }

        switch ($this->value[0]) {
            case self::VALUE_TYPE_SCALAR:
                return $this->value[1];
            case self::VALUE_TYPE_ARRAY:
                return $this->value[1][$this->value[2]];
            case self::VALUE_TYPE_OBJECT:
                return $this->value[1]->{$this->value[2]};
        }
    }

    /**
     * Check whether the value has a member
     *
     * @param mixed $member
     * @return bool
     */
    public function hasMember($member) : bool
    {
        if ($this->isObject()) {
            return property_exists($this->getValue(), $member);
        } elseif ($this->isArray()) {
            return array_key_exists($member, $this->getValue());
        }

        return false;
    }

    /**
     * Get a member
     *
     * @param mixed $member
     * @return self
     */
    public function getMember($member) : self
    {
        $helper = new self($this->state, $this->spec, $this->getValue(), $member);
        $helper->path = $this->path;
        $helper->path[] = $member;
        return $helper;
    }

    /**
     * Get a member value
     *
     * @param mixed $member
     * @return mixed
     */
    public function getMemberValue($member, ...$default)
    {
        if ($this->isObject()) {
            return $this->getValue()->$member ?? $default[0] ?? null;
        } elseif ($this->isArray()) {
            return $this->getValue()[$member] ?? $default[0] ?? null;
        } else {
            return null;
        }
    }

    /**
     * Get the value path
     *
     * @return array
     */
    public function getPath() : array
    {
        return $this->path;
    }

    /**
     * Get the value pointer
     *
     * @return string
     */
    public function getPointer() : string
    {
        return Util::encodePointer($this->path);
    }

    /**
     * Get the value type
     *
     * @return string
     */
    public function getType() : string
    {
        $value = $this->getValue();
        switch ($type = gettype($value)) {
            case 'string':
            case 'boolean':
            case 'integer':
            case 'array':
            case 'object':
                return $type;
            case 'NULL':
                return 'null';
            case 'double':
                return $this->isInteger() ? 'integer' : 'number';
            default:
                return 'unknown';
        }
    }

    /**
     * Check the value type
     *
     * @param string $type
     * @param bool $default What to return for undefined document values
     * @return bool
     */
    public function isType(string $type, bool $default = true) : bool
    {
        // undefined values could be any type, so return the default
        if (!$this->isDefined()) {
            return $default;
        }

        // check that the type being checked for is valid
        if (!$this->spec->type($type) && !$this->spec->standard('allowUndefinedTypes')) {
            throw Exception::INVALID_TYPE($type);
        }

        // check the value's type
        $value = $this->getValue();
        switch ($type) {
            // primitive type rules
            case 'null':
                return is_null($value);
            case 'boolean':
                return is_bool($value);
            case 'number':
                return is_numeric($value) && !is_string($value);
            case 'integer':
                if (is_int($value)) {
                    return true;
                }
                $isFloatingInt = $this->isType('number') && floor($value) == $value;
                if ($isFloatingInt) {
                    if ($this->spec->standard('allowIntegerWithFractionalPart')) {
                        return true;
                    } elseif ($value > PHP_INT_MAX || $value < PHP_INT_MIN) {
                        // Integers larger than PHP's internal integer type are parsed as a double, so
                        // use the integer type for them. Note that floating-point values with a zero
                        // fractional component (e.g. 3.0000) are *not* integers before draft-06, and
                        // should not be treated as such. Unfortunately, there is no way to tell the
                        // difference once the number is outside the bounds of PHP's integer type, so we
                        // just assume that the number should be an int if the fractional component is
                        // zero or missing, as this is the most common use-case.
                        return true;
                    }
                }
                return false;
            case 'string':
                return is_string($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value);
            case 'any':
                return true; // 'any' type is universally valid
            
            // default case where the type being checked for is unknown
            default:
                return $default;
        }
    }

    /**
     * Check the value is one of the given types
     *
     * @param string[] $types
     * @return bool
     */
    public function isTypes(string ...$types) : bool
    {
        foreach ($types as $type) {
            if ($this->isType($type)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Run a callback for each child member
     *
     * @param \Closure $callback function(self $value, scalar $member)
     * @param array $priority Run in order for these members first, then everything else
     */
    public function each(\Closure $callback, ...$priority)
    {
        if ($this->isDefined() && $this->isTypes('array', 'object')) {
            foreach ($priority as $member) {
                if ($this->hasMember($member)) {
                    $callback($this->getMember($member), $member);
                }
            }
            foreach ($this->getValue() as $member => $value) {
                if (!in_array($member, $priority)) {
                    $callback($this->getMember($member), $member);
                }
            }
        }
    }

    /**
     * Get the target at the given path
     *
     * @param array $path
     * @return self
     */
    private function getTargetAtPath(array $path) : self
    {
        // this is the path target
        if (!count($path)) {
            return $this;
        }

        // we need a child, so must be a container
        if (!$this->isTypes('object', 'array')) {
            throw new \Exception();
        }

        // recurse to child
        $child = $this->getMember(array_shift($path));
        return $child->getTargetAtPath($path);
    }

    /**
     * Get the target at the given pointer
     *
     * @param string $pointer
     * @return self
     */
    public function getTargetAtPointer(string $pointer) : self
    {
        try {
            return $this->getTargetAtPath(Util::decodePointer($pointer));
        } catch (\Throwable $e) {
            throw Exception::INVALID_POINTER_TARGET($pointer);
        }
    }

    /**
     * Count the number of child members
     *
     * @return int
     */
    public function count() : int
    {
        return $this->isTypes('array', 'object') ? count((array)$this->getValue()) : 0;
    }
}
