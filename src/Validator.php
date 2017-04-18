<?php

namespace Dogfood;

use Erayd\JsonSchemaInfo\SchemaInfo;

use Dogfood\Exception\Exception;
use Dogfood\Internal\State;
use Dogfood\Internal\Schema;
use Dogfood\Internal\ValueHelper;

/**
 * Main public interface for library
 *
 * @package erayd/dogfood
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class Validator extends Internal\BaseValidator
{
    // Available spec versions
    const SPEC_DRAFT_03 = SchemaInfo::SPEC_DRAFT_03;
    const SPEC_DRAFT_04 = SchemaInfo::SPEC_DRAFT_04;
    const SPEC_DRAFT_05 = SchemaInfo::SPEC_DRAFT_05;

    // Available config options
    const OPT_ALL               = 0;
    const OPT_SPEC_VERSION      = 1;
    const OPT_EXCEPTIONS        = 2;
    const OPT_APPLY_DEFAULTS    = 3;
    const OPT_VALIDATE_SCHEMA   = 4;
    const OPT_FETCH_PROVIDER    = 5;

    /** @var array Configuration options */
    protected $options = [
        // force spec version (default will autodetect, then fallback to draft-04)
        self::OPT_SPEC_VERSION      => null,

        // whether to throw exceptions rather than returning a false validation result
        self::OPT_EXCEPTIONS        => false,

        // whether to set undefined values to their defaults, if a default is available
        self::OPT_APPLY_DEFAULTS    => false,

        // whether to also validate the schema
        self::OPT_VALIDATE_SCHEMA   => true,  // whether to also validate the schema

        // callback used for fetching remote resources | function(string $uri) : string
        self::OPT_FETCH_PROVIDER    => 'file_get_contents',
    ];

    /**
     * Create a new Validator instance
     *
     * @api
     *
     * @param \StdClass $schema  Schema definition
     * @param string    $uri     Schema URI
     * @param array     $options Config options
     */
    public function __construct(\StdClass $schema = null, string $uri = null, array $options = [])
    {
        $state = new State();

        // load user options
        foreach ($options as $option => $value) {
            $this->options[$option] = $value;
        }
        $state->setOption(self::OPT_ALL, $this->options);

        // call parent constructor
        parent::__construct($state, $schema, $uri);
    }

    /**
     * Validate a decoded JSON document against the schema
     *
     * Returns true if validation succeeded, false otherwise.
     *
     * @api
     *
     * @param  mixed $document JSON document to validate
     * @return bool
     */
    public function validate(&$document) : bool
    {
        try {
            $this->validateInstance(new ValueHelper($this->state, $document), $this->definition);
            return true;
        } catch (\Exception $e) {
            if ($this->state->getOption(Validator::OPT_EXCEPTIONS)) {
                throw $e;
            }
            return false;
        }
    }

    /**
     * Add a schema to the local cache
     *
     * Returns true if the schema was successfully registered, or false
     * if a schema was already registered against the provided URI.
     *
     * @api
     *
     * @param string    $uri        Schema URI
     * @param \StdClass $definition Decoded schema definition
     * @param string    $standard   Which schema standard to use
     * @return bool
     */
    public function addSchema(string $uri, \StdClass $definition = null, string $standard = null) : bool
    {
        try {
            // ensure fragment is present
            $uri = implode('#', array_pad(explode('#', $uri, 2), 2, ''));

            // check for already-registered schema
            if ($this->state->haveSchema($uri)) {
                return false;
            }

            // get standard
            if (!is_null($standard)) {
                $standard = new SchemaInfo($standard);
            }

            // import the schema
            new Schema($this->state, $uri, $definition, $standard);
        } catch (\Exception $e) {
            throw SchemaException::SCHEMA_IMPORT_ERROR($uri, $e->getMessage(), $e);
        }

        return true;
    }
}
