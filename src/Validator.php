<?php

namespace JsonValidator;

use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\State;
use JsonValidator\Internal\Util;
use JsonValidator\Internal\ValueHelper;

/**
 * Main library interface
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class Validator extends BaseInstance
{
    /**
     * Create a new instance
     *
     * @api
     */
    public function __construct(array $options = [])
    {
        parent::__construct(new State());

        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }
    }

    /**
     * Set an option
     *
     * @api
     *
     * @param int $option
     * @param mixed $value
     */
    public function setOption(int $option, $value)
    {
        $this->state->setOption($option, $value);
    }

    /**
     * Add a schema and return its URI
     *
     * @api
     *
     * @param  mixed  $definition Decoded schema definition
     * @param  string $uri        Schema URI
     * @param  string $standard   JSON-Schema standard to use for this schema
     * @return string
     */
    public function addSchema($definition, string $uri = null, $standard = null) : string
    {
        // ensure we have a definition
        if (is_null($definition)) {
            if (is_null($uri)) {
                throw Exception::NULL_DEFINITION();
            }
            $definition = $this->state->getRemote($uri);
        }

        // instantiate schema
        $schema = new Schema($this->state, $definition, $uri, $standard);
        return $schema->getURI();
    }

    /**
     * Validate a JSON document
     *
     * @param mixed  $document Decoded JSON document
     * @param string $uri      Schema URI for validation
     */
    public function validate($document, string $uri)
    {
        try {
            // get validation schema
            $schema = $this->state->getSchema(Util::clampURI($uri));

            // get document helper
            $documentHelper = new ValueHelper($this->state, $schema->getSpec(), $document);

            // validate document
            $schema->validate($documentHelper);
            return true;
        } catch (\Throwable $e) {
            if ($this->state->getOption(Config::THROW_EXCEPTIONS)) {
                throw $e;
            }
            return false;
        }
    }
}
