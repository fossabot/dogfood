<?php

namespace JsonValidator\Internal;

use JsonValidator\Exception;
use JsonValidator\Config;
use JsonValidator\Validator;

/**
 * Global state
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class State
{
    /** @var array Options */
    private $options = Config::DEFAULT_CONFIG;

    /** @var array Remote fetch cache */
    private $remoteCache = [];

    /** @var array Schema instance cache */
    private $schemaCache = [];

    /** @var array Singleton instance cache */
    private $instanceCache = [];

    /** @var array Handler instance cache */
    private $handlerCache = [];

    /** @var array Map of keyword aliases */
    const KEYWORD_MAP = [
        'id' => '$id',
        'additionalItems' => 'items',
        'additionalProperties' => 'properties',
        'patternProperties' => 'properties',
        'divisibleBy' => 'multipleOf',
    ];

    /**
     * Set an option
     *
     * @param int $option
     * @param mixed $value
     */
    public function setOption(int $option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Get an option
     *
     * @param int $option
     * @return mixed
     */
    public function getOption(int $option)
    {
        return $this->options[$option] ?? null;
    }

    /**
     * Check whether a schema is registered against the given URI
     *
     * @param string $uri
     * @return bool
     */
    public function haveSchema(string $uri) : bool
    {
        $uri = Util::clampURI($uri);

        if (array_key_exists($uri, $this->schemaCache)) {
            return true;
        }

        return array_key_exists(preg_replace('/#.*$/', '', $uri), $this->schemaCache);
    }

    /**
     * Register a schema against a URI
     *
     * @param string $uri
     * @param Schema $schema
     */
    public function registerSchema(string $uri, Schema $schema)
    {
        $uri = Util::clampURI($uri);

        if ($this->haveSchema($uri)) {
            throw Exception::SCHEMA_ALREADY_REGISTERED($uri);
        }

        $this->schemaCache[$uri] = $schema;
    }

    /**
     * Get the schema for a URI
     *
     * @param string $uri
     * @return Schema
     */
    public function getSchema(string $uri) : Schema
    {
        $uri = Util::clampURI($uri);

        // check for the full URI
        if (array_key_exists($uri, $this->schemaCache)) {
            return $this->schemaCache[$uri];
        }

        // check for the document URI & fetch / create schema if missing
        list($schemaURI, $ref) = array_pad(explode('#', $uri, 2), 2, '');
        if (!$this->haveSchema($schemaURI)) {
            $definition = json_decode($this->getRemote($schemaURI));
            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw Exception::JSON_DECODE_ERROR(json_last_error_msg());
            }
            $schema = new Schema($this, $definition, $schemaURI);
        }

        // return target schema
        if (!strlen($ref)) {
            return $schema;
        }
        return $this->schemaCache[$schemaURI]->getSub('#' . $ref);
    }

    /**
     * Fetch remote resource
     *
     * @param string $uri
     * @return string
     */
    public function getRemote(string $uri) : string
    {
        if (!array_key_exists($uri, $this->remoteCache)) {
            if (!$this->getOption(Config::FETCH_REMOTE)) {
                throw Exception::REMOTE_FETCH_DISABLED($uri);
            }

            set_error_handler(function ($errno, $message) use ($uri) {
                throw Exception::FETCH_ERROR($uri, $message);
            });
            try {
                return $this->getOption(Config::FETCH_HANDLER)($uri);
            } catch (Exception $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw Exception::FETCH_ERROR($uri, $e->getMessage());
            } finally {
                restore_error_handler();
            }
        }

        return $this->remoteCache[$uri];
    }

    /**
     * Set a singleton instance
     *
     * @param string $className
     * @param mixed $instance
     */
    public function setInstance(string $className, $instance)
    {
        $this->instanceCache[$className] = $instance;
    }

    /**
     * Get a singleton instance
     *
     * @param string $className
     * @return mixed
     */
    public function getInstance(string $className)
    {
        if (!array_key_exists($className, $this->instanceCache)) {
            $this->setInstance($className, new $className($this));
        }

        return $this->instanceCache[$className];
    }

    /**
     * Get handler instance for keyword
     *
     * @param string $keyword
     * @return mixed
     */
    public function getHandler(string $keyword)
    {
        if (!array_key_exists($keyword, $this->handlerCache)) {
            // dereference keyword aliases
            $realKeyword = array_key_exists($keyword, self::KEYWORD_MAP) ? self::KEYWORD_MAP[$keyword] : $keyword;

            // convert to class name
            $handlerClass = sprintf(
                '%s\\Handlers\\%sHandler',
                __NAMESPACE__,
                ucfirst(preg_replace('/[^a-z0-9]/i', '', $realKeyword))
            );

            // get instance
            $this->handlerCache[$keyword] = $this->getInstance($handlerClass);
        }

        return $this->handlerCache[$keyword];
    }
}
