<?php

namespace Dogfood\Internal;

use Dogfood\Exception\RuntimeException;
use Dogfood\Exception\SchemaException;

use Dogfood\Validator;
use Dogfood\Internal\BaseValidator;

/**
 * Internal state
 *
 * @package erayd/dogfood
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class State
{
    /** @var array Handler map */
    private $handlerMap = [
        'additionalItems'       => 'items',
        'patternProperties'     => 'properties',
        'additionalProperties'  => 'properties',
        'divisibleBy'           => 'multipleOf',
        'exclusiveMinimum'      => 'minimum',
        'exclusiveMaximum'      => 'maximum',
    ];

    /** @var array Handler cache */
    private $handlerCache = [];

    /** @var array Cache for remote documents */
    private $fetchCache = [];

    /** @var array Schema definitions */
    private $schemas = [];

    /** @var array Validation options */
    private $options = [];

    /** @var BaseValidator Validator instance */
    private $validator = null;

    /**
     * Fetch a remote resource
     *
     * @param string $uri
     * @param string $data
     * @return string
     */
    public function fetch(string $uri, string $data = null) : string
    {
        if (!array_key_exists($uri, $this->fetchCache)) {
            set_error_handler(function (int $code, string $message) use ($uri) {
                throw RuntimeException::FETCH($uri);
            });
            $scheme = parse_url($uri, \PHP_URL_SCHEME);
            if (!in_array($scheme, ['http', 'https', 'file'])) {
                throw RuntimeException::FETCH_SCHEME($scheme, $uri);
            }
            $this->fetchCache[$uri] = $this->getOption(Validator::OPT_FETCH_PROVIDER)($uri);
            restore_error_handler();
        }

        return $this->fetchCache[$uri];
    }

    /**
     * Register a schema definition
     *
     * @param string $uri
     * @param ObjectHelper $definition
     */
    public function registerSchema(string $uri, ObjectHelper $definition)
    {
        if ($this->haveSchema($uri)) {
            throw SchemaException::SCHEMA_REGISTER_ONCE_ONLY($uri);
        }

        $this->schemas[$uri] = $definition;
    }

    /**
     * Check whether a schema is registered against the provided uri
     *
     * @param string $uri
     * @return bool
     */
    public function haveSchema(string $uri) : bool
    {
        return array_key_exists($uri, $this->schemas);
    }

    /**
     * Get a registered schema definition
     *
     * @param string $uri
     */
    public function getSchema(string $uri)
    {
        // throw a tantrum if the desired schema URI isn't registered
        if (!$this->haveSchema($uri)) {
            throw SchemaException::SCHEMA_NOT_REGISTERED($uri);
        }

        return $this->schemas[$uri];
    }

    /**
     * Get a keyword handler
     *
     * @param string $keyword
     * @return mixed Handler instance
     */
    public function getHandler(string $keyword)
    {
        // dereference mapped handler keywords
        if (array_key_exists($keyword, $this->handlerMap)) {
            return $this->getHandler($this->handlerMap[$keyword]);
        }

        // instantiate handler class if missing
        if (!isset($this->handlerCache[$keyword])) {
            $handlerClass = sprintf('%s\\Handlers\\%sHandler', __NAMESPACE__, ucfirst($keyword));
            if (!class_exists($handlerClass)) {
                throw RuntimeException::MISSING_HANDLER($keyword);
            }
            $this->handlerCache[$keyword] = new $handlerClass($this);
        }

        // return handler
        return $this->handlerCache[$keyword];
    }

    /**
     * Get options
     *
     * @param int $option
     * @return mixed
     */
    public function getOption(int $option = null)
    {
        if (!is_null($option)) {
            return $this->options[$option];
        }

        return $this->options;
    }

    /**
     * Set options
     *
     * @param int $option
     * @param mixed $value
     */
    public function setOption(int $option, $value)
    {
        if ($option == Validator::OPT_ALL) {
            $this->options = $value;
        } else {
            $this->options[$option] = $value;
        }
    }

    /**
     * Set the validator instance
     *
     * @param BaseValidator $validator
     */
    public function setValidator(BaseValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Get the validator instance
     *
     * @return BaseValidator
     */
    public function getValidator() : BaseValidator
    {
        return $this->validator;
    }
}
