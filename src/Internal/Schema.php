<?php

namespace Dogfood\Internal;

use Sabre\Uri;
use Erayd\JsonSchemaInfo\SchemaInfo;

use Dogfood\Validator;

use Dogfood\Exception\RuntimeException;
use Dogfood\Exception\SchemaException;
use Dogfood\Internal\State;

/**
 * Schema management
 *
 * @package erayd/dogfood
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class Schema extends BaseInstance
{
    const SPEC_DEFAULT = SchemaInfo::SPEC_DRAFT_04;

    /** @var string Schema standard */
    protected $spec = null;

    /** @var array List of ref objects and their resolution bases */
    protected $refs = [];

    /** @var ObjectHelper Base definition */
    protected $definition = null;

    /**
     * Create a new Schema instance
     *
     * @param State $state
     * @param string $uri
     * @param \StdClass $definition
     * @param SchemaInfo $spec
     */
    public function __construct(
        State $state = null,
        string $uri = null,
        \StdClass $definition = null,
        SchemaInfo $spec = null
    ) {
        parent::__construct($state);

        // use internal uri if one isn't provided
        if (is_null($uri)) {
            $uri = 'dogfood://unidentified-schema/' . sha1(random_bytes(16));
        }

        // fetch remote definition
        if (is_null($definition)) {
            $definition = json_decode($this->state->fetch($uri));
            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw RuntimeException::JSON_DECODE(json_last_error_msg());
            }
            if (!($definition instanceof \StdClass)) {
                throw SchemaException::REF_TARGET_INVALID_TYPE(gettype($definition), $uri);
            }
        }

        // wrap definition
        $this->definition = new ObjectHelper($definition, ObjectHelper::STICKY);

        // use internal definition for spec schemas - this prevents the user from overriding a standard
        // spec definition, which prevents injection of dodgy specs when the schema input is untrusted.
        $stdSpecName = SchemaInfo::getSpecName($uri) ?: SchemaInfo::getSpecName($this->definition->getProperty('id', ''));
        if ($stdSpecName) {
            $definitionSpec = new SchemaInfo($stdSpecName);
            $this->definition = new ObjectHelper($definitionSpec->getSchema(), ObjectHelper::STICKY);
        }

        // set $this as 'schema' metadata property
        $this->definition->setMeta('schema', $this);

        // set spec
        $this->spec = $spec ?: new SchemaInfo($this->definition->getProperty('$schema', self::SPEC_DEFAULT));

        // set uri & register root definition
        $this->uri = implode('#', array_pad(explode('#', $uri, 2), 2, '')); // ensure fragment is present
        if (!$this->definition->hasProperty('id') || $this->definition->id != $this->uri) {
            // only register the uri for the root if the schema doesn't already do it via "id"
            $this->state->registerSchema($this->uri, $this->definition);
        }

        // hydrate local identifiers & discover refs
        $this->hydrate($this->definition, $this->uri);

        // validate against spec schema...
        if ($this->state->getOption(Validator::OPT_VALIDATE_SCHEMA)) {
            $specURI = $this->spec->getURI();

            // ...but only if *this* schema isn't the spec (spec schemas should be assumed valid)!
            if ($this->uri != $specURI) {
                // import spec if missing
                if (!$this->state->haveSchema($specURI)) {
                    new self($this->state, $specURI, $this->spec->getSchema());
                }

                // validate against spec schema
                $specDefinition = $this->state->getSchema($this->spec->getURI());
                $targetValue = new ValueHelper($this->state, $this->definition->getObject());
                $this->state->getValidator()->validateInstance($targetValue, $specDefinition);
            }
        }
    }

    /**
     * Get the schema spec
     *
     * @return SchemaInfo
     */
    public function getSpec() : SchemaInfo
    {
        return $this->spec;
    }

    /**
     * Get the schema definition at the given JSON pointer
     *
     * @param string $pointer
     * @return ObjectHelper
     */
    private function getTargetDefinition(string $pointer) : ObjectHelper
    {
        $definition = $this->definition;

        // catch empty references to root
        if ($pointer == '#') {
            return $definition;
        }

        // check pointer syntax
        if (!preg_match('|^#(/[^/]+)*$|', $pointer)) {
            throw SchemaException::INVALID_POINTER_FORMAT($pointer);
        }

        // decode pointer
        $parts = array_slice(array_map(function ($item) {
            return strtr($item, ['~1' => '/', '~0' => '~']);
        }, explode('/', rawurldecode($pointer))), 1);

        // step down target
        while (count($parts)) {
            $targetName = array_shift($parts);
            if ($definition instanceof ObjectHelper && $definition->hasProperty($targetName)) {
                $definition = $definition->$targetName;
            } elseif (is_array($definition) && array_key_exists($targetName, $definition)) {
                $definition = $definition[$targetName];
            } else {
                throw SchemaException::INVALID_POINTER_TARGET($pointer);
            }
        }

        // return target definition
        return $definition;
    }

    /**
     * Resolve a URI or pointer against a base
     *
     * @param string $base
     * @param string $uri
     * @return string
     */
    private static function resolve(string $base, string $uri) : string
    {
        $target = explode('#', Uri\resolve($base, $uri), 2);
        return implode(array_pad($target, 2, ''), '#');
    }

    /**
     * Hydrate identifiers & note reference resolution base
     *
     * @param ObjectHelper $definition
     * @param string $base
     */
    private function hydrate(ObjectHelper $definition, string $base)
    {
        // update base & register identified schemas
        if ($definition->hasProperty('id')) {
            $id = $definition->id;
            $base = self::resolve($base, $id);
            if (preg_match('/^#[a-z][a-z0-9-_.:]*$/i', $id)) {
                // this is a valid local identifier
                $this->state->registerSchema($base, $definition);
            } elseif (substr($id, 0, 1) != '#') {
                // this is a valid non-local identifier
                $this->state->registerSchema($base, $definition);
            } else {
                // this is a fragment, but is not a valid id format
                throw SchemaException::INVALID_ID_FORMAT($id);
            }
        }

        // remember resolution base for later
        $this->refs[$definition->getPath()] = $base;

        // process children
        $definition->each(function ($value, $keyword) use ($base) {
            $this->hydrateValueProcessor($value, $base, $keyword);
        });
    }

    /**
     * Process hydration child values
     *
     * @param mixed $value
     * @param string $base
     * @param string $keyword
     */
    private function hydrateValueProcessor($value, string $base, string $keyword = null)
    {
        // unroll arrays
        if (is_array($value)) {
            foreach ($value as $singleValue) {
                $this->hydrateValueProcessor($singleValue, $base, $keyword);
            }
            return;
        }

        // ensure we're dealing with an ObjectHelper
        if (!($value instanceof ObjectHelper)) {
            return;
        }

        // limit to schema-containing keywords
        try {
            if (is_null($keyword)) {
                $this->hydrate($value, $base);
            } elseif ($this->spec->keyword($keyword, $constraints)) {
                if ($constraints->objectIsSchema) {
                    // value is a schema
                    $this->hydrate($value, $base);
                } elseif ($constraints->childObjectIsSchema) {
                    // value contains subschema children, so process them
                    $value->each(function ($value, $keyword) use ($base) {
                        $this->hydrateValueProcessor($value, $base);
                    });
                }
            }
        } catch (\InvalidArgumentException $e) {
            // if this isn't a valid keyword, then we don't care about it
            return;
        }
    }

    /**
     * Dereference $ref schemas
     *
     * @param ObjectHelper $definition
     * @return ObjectHelper
     */
    protected function dereference(ObjectHelper $definition) : ObjectHelper
    {
        // if there is no $ref, no action needs to be taken
        if (!$definition->hasProperty('$ref')) {
            return $definition;
        }

        // resolve $ref against base
        if (isset($this->refs[$definition->getPath()])) {
            $base = $this->refs[$definition->getPath()];
        } else {
            // no base available (target probably isn't a standard schema),
            // so walk up the path looking for the next available base
            $path = explode('/', $definition->getPath());
            while (array_pop($path) !== null) {
                if (array_key_exists(implode('/', $path), $this->refs)) {
                    $base = $this->refs[implode('/', $path)];
                    break;
                }
            }
            if (!isset($base)) {
                // unable to find a base to resolve against
                throw RuntimeException::REF_BASE_NOT_SET();
            }
        }

        $ref = $definition->getProperty('$ref');
        $uri = self::resolve($base, $ref);

        // get schema definition
        if ($this->state->haveSchema($uri)) {
            // get registered schema definition
            $definition = $this->state->getSchema($uri);
        } else {
            // ref uri is not directly registered, so find it
            if (substr($ref, 0, 1) === '#') {
                // $ref is a JSON pointer, so find it in the current schema
                $definition = $this->getTargetDefinition($ref);
            } else {
                // $ref points to another document, so look there
                $parts = array_pad(explode('#', $uri, 2), 2, '');
                $targetSchemaURI = $parts[0] . '#';
                try {
                    // get target schema from the state cache
                    $targetSchema = $this->state->getSchema($targetSchemaURI)->getMeta('schema');
                } catch (SchemaException $e) {
                    // we don't know about this schema at all, so fetch / create it
                    $targetSchema = new self($this->state, $targetSchemaURI);
                }
                $definition = $targetSchema->getTargetDefinition('#' . $parts[1]);
            }
        }

        // recursively dereference in case the target is itself a reference
        return $definition->getMeta('schema')->dereference($definition);
    }
}
