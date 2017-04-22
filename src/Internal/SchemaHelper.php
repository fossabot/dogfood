<?php

namespace Dogfood\Internal;

use Dogfood\Exception\RuntimeException;

/**
 * Helper for working with objects
 *
 * @package erayd/dogfood
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class SchemaHelper
{
    const OBJECT_TO_HELPER       = 1; // convert object properties to SchemaHelper instances on access
    const ARRAY_OBJECT_TO_HELPER = 2; // if OBJECT_TO_HELPER is enabled, also apply it to array members
    const INHERIT_ALIASES        = 2; // OBJECT_TO_HELPER inherits aliases
    const INHERIT_META           = 3; // OBJECT_TO_HELPER inherits metadata

    const STICKY = self::OBJECT_TO_HELPER | self::ARRAY_OBJECT_TO_HELPER | self::INHERIT_META | self::INHERIT_META;

    /** @var int Config options */
    protected $options = 0;

    /** @var \StdClass object reference */
    protected $object = null;

    /** @var array Property aliases */
    protected $propertyAlias = [];

    /** @var array Metadata */
    protected $meta = [];

    /** @var string Object path */
    protected $path = '#';

    /**
     * Create a new SchemaHelper instance
     *
     * @param \StdClass $object
     */
    public function __construct(\StdClass $object, int $options = 0, $path = '#')
    {
        $this->object = $object;
        $this->options = $options;
        $this->path = $path;
    }

    /**
     * Get an object property directly
     *
     * @param string $propertyName
     * @return mixed
     */
    public function __get(string $propertyName)
    {
        return $this->getProperty($propertyName);
    }

    /**
     * Set an object property directly
     *
     * @param string $propertyName
     * @param mixed $value
     */
    public function __set(string $propertyName, $value)
    {
        $this->setProperty($propertyName, $value);
    }

    /**
     * Get the object as a JSON string
     *
     * @return string
     */
    public function __toString() : string
    {
        return json_encode($this->object, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT);
    }

    /**
     * Check whether the object has a property
     *
     * @param string $propertyName
     * @return bool
     */
    public function hasProperty(string $propertyName) : bool
    {
        // dereference aliases
        if (array_key_exists($propertyName, $this->propertyAlias)) {
            $propertyName = $this->propertyAlias[$propertyName];
        }

        return property_exists($this->object, $propertyName);
    }

    /**
     * Get an object property
     *
     * @param string $propertyName
     * @param mixed $default
     * @return mixed
     */
    public function &getProperty(string $propertyName, ...$default)
    {
        // encode path element
        $propertyNameEncoded = strtr($propertyName, ['~' => '~0', '/' => '~1']);

        // dereference aliases
        if (array_key_exists($propertyName, $this->propertyAlias)) {
            $propertyName = $this->propertyAlias[$propertyName];
        }

        if (property_exists($this->object, $propertyName)) {
            // return defined property
            $value = &$this->object->$propertyName;
        } elseif (array_key_exists(0, $default)) {
            // return default if undefined
            $value = &$default[0];
        } else {
            // no value available
            throw RuntimeException::PROPERTY_NOT_SET($propertyName);
        }

        // wrap objects as SchemaHelper instances
        if ($this->options & self::OBJECT_TO_HELPER) {
            if (is_array($value) && $this->options & self::ARRAY_OBJECT_TO_HELPER) {
                $result = [];
                foreach ($value as $key => &$arrayValue) {
                    if ($arrayValue instanceof \StdClass) {
                        $result[$key] = $this->toHelper($arrayValue, "{$this->path}/$key/$propertyNameEncoded");
                    } else {
                        $result[$key] = &$arrayValue;
                    }
                }
            } elseif ($value instanceof \StdClass) {
                $result = $this->toHelper($value, "{$this->path}/$propertyNameEncoded");
            } else {
                $result = &$value;
            }
            return $result;
        }

        return $value;
    }

    /**
     * Convert an object into an SchemaHelper instance
     *
     * @param \StdClass $object
     * @param string $path
     * @return self
     */
    private function toHelper(\StdClass $object, string $path) : self
    {
        $object = new self($object, $this->options, $path);

        // inherit aliases
        if ($this->options & self::INHERIT_ALIASES) {
            $object->propertyAlias = $this->propertyAlias;
        }
        // inherit metadata
        if ($this->options & self::INHERIT_META) {
            $object->meta = $this->meta;
        }

        return $object;
    }

    /**
     * Set an object property
     *
     * @param string $propertyName
     * @param mixed $value
     */
    public function setProperty(string $propertyName, $value)
    {
        // dereference aliases
        if (array_key_exists($propertyName, $this->propertyAlias)) {
            $propertyName = $this->propertyAlias[$propertyName];
        }

        $this->object->$propertyName = $value;
    }

    /**
     * Get the underlying object
     *
     * @return \StdClass
     */
    public function &getObject() : \StdClass
    {
        return $this->object;
    }

    /**
     * Set a property alias
     *
     * @param string $propertyName
     * @param string $alias
     */
    public function setAlias(string $propertyName, string $alias)
    {
        $this->propertyAlias[$alias] = $propertyName;
    }

    /**
     * Run a callback against each property
     *
     * @param callable $callback (mixed &$value, string $propertyName)
     */
    public function each(callable $callback)
    {
        foreach ($this->object as $propertyName => &$value) {
            $callback($this->getProperty($propertyName), $propertyName);
        }
    }

    /**
     * Get the currently set options
     *
     * @return int
     */
    public function getOptions() : int
    {
        return $this->options;
    }

    /**
     * Set a metadata property
     *
     * @param string $metaName
     * @param mixed $value
     */
    public function setMeta(string $metaName, $value)
    {
        $this->meta[$metaName] = $value;
    }

    /**
     * Get a metadata property
     *
     * @param string $metaName
     * @param mixed $default
     * @return mixed
     */
    public function getMeta(string $metaName, $default = null)
    {
        return isset($this->meta[$metaName]) ? $this->meta[$metaName] : $default;
    }

    /**
     * Get the object path
     *
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }
}
