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
class Schema extends BaseInstance
{
    const DEFAULT_SPEC = 'http://json-schema.org/draft-06/schema#';

    /** @var ValueHelper $definition */
    protected $definition = null;

    /** @var self Schema root */
    protected $root = null;

    /** @var string Schema URI */
    protected $uri = null;

    /** @var SchemaInfo Schema spec */
    protected $spec = null;

    /** @var string ID keyword */
    protected $id = '$id';

    /** @var array Subschema cache */
    protected $subCache = [];

    /**
     * Create a new schema instance
     *
     * @param State $state
     * @param mixed $definition
     * @param string $uri
     * @param string $spec
     * @param self $root
     */
    public function __construct(State $state, $definition, string $uri = null, string $spec = null, self $root = null)
    {
        parent::__construct($state);

        // set up spec
        if ($root) {
            $this->spec = $root->getSpec();
        } elseif (is_object($definition) && property_exists($definition, '$schema')) {
            $this->spec = new SchemaInfo($definition->{'$schema'});
        } else {
            $this->spec = new SchemaInfo($spec ?? self::DEFAULT_SPEC);
        }
        $this->id = $this->spec->getIdKeyword();

        // set root
        $this->root = $root ?? $this;

        // set definition
        if (is_object($definition) && $definition instanceof ValueHelper) {
            $this->definition = $definition;
        } else {
            $this->definition = new ValueHelper($this->state, $this->spec, $definition);
        }

        // set uri
        if (is_null($uri)) {
            $this->uri = 'json-validator://' . sha1(random_bytes(16)) . '/schema#';
        } else {
            $this->uri = Util::clampURI($uri);
        }

        // register uri with state (root schema) or root (subschema)
        if (is_null($root)) {
            $this->state->registerSchema($this->uri, $this);
        } else {
            $this->root->subCache[$this->getPointer()] = $this;
        }

        // update uri from id & register as appropriate
        if ($id = $this->getMemberValue($this->id, null)) {
            printf("\nSet ID %s for %s\n", $id, $this->definition->getPointer());
            if (substr($id, 0, 1) == '#') {
                // fragment-only local identifier, so just add it to the local subschema cache
                if (!preg_match('/^#[a-z][a-z0-9-_:.]*/i', $id)) {
                    throw Exception::INVALID_IDENTIFIER($id);
                }
                $this->root->subCache[$id] = $this;
            } else {
                // relative or absolute uri, so register globally & change uri
                $idURI = Util::clampURI($id, $this->uri);
                if ($idURI != $this->uri) {
                    $this->uri = $idURI;
                    $this->state->registerSchema($idURI, $this);
                }
            }
            printf("\nResulting URI %s for %s\n", $this->uri, $this->definition->getPointer());
        }

        // locate & register all identified subschemas
        printf(
            "\nStart hydrating @ %s\n",
            $this->definition->getPointer(),
            json_encode($this->getValue(), \JSON_PRETTY_PRINT|\JSON_UNESCAPED_SLASHES)
        );
        $this->hydrate($this->definition, true);
    }

    /**
     * Walk through child members and find identified subschemas
     *
     * @param ValueHelper $definition
     * @param bool $isContainer
     */
    private function hydrate(ValueHelper $definition, bool $isSchema)
    {
        if (!$isSchema || $definition->isArray()) {
            // definition is a container or an array, so hydrate its children
            $definition->each(function(ValueHelper $child) {
                $this->hydrate($child, true);
            });
        } else {
            // definition is a schema...
            if ($definition->hasMember($this->id) && $definition !== $this->definition) {
                // ...and has an identifier, so create a schema for it
                $this->getSub(null, $definition);
            } else {
                // ...and has no identifier, so iterate those of its children...
                $definition->each(function(ValueHelper $child, $member) {
                    if ($info = $this->spec->validation($member) ?? $this->spec->metadata($member)) {
                        if ($info->isSchema) {
                            // ...which are schemas
                            $this->hydrate($child, true);
                        } elseif ($info->isSchemaContainer) {
                            // ...which are containers
                            $this->hydrate($child, false);
                        }
                    }
                });
            }
        }
    }

    /**
     * Pass nonexistant method calls to definition helper
     *
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    public function __call(string $methodName, array $args)
    {
        return $this->definition->$methodName(...$args);
    }

    /**
     * Get schema URI
     *
     * @return string
     */
    public function getURI() : string
    {
        return $this->uri;
    }

    /**
     * Get schema spec
     *
     * @return SchemaInfo
     */
    public function getSpec() : SchemaInfo
    {
        return $this->spec;
    }

    /**
     * Get a subschema at a pointer target
     *
     * @return self
     */
    public function getSub(string $pointer = null, ValueHelper $definition = null) : self
    {
        // TODO walk up path and find closest sub instance, then get URI from that for dereferencing
        if (is_null($pointer) && $definition) {
            $pointer = $definition->getPointer();
        }
        printf("\nGetSub @ %s\n", $pointer);

        if (!array_key_exists($pointer, $this->root->subCache)) {
            $subURI = Util::clampURI($pointer, $this->getURI());
            return $this->root->subCache[$pointer] = new self(
                $this->state, $definition ?? $this->root->getTargetAtPointer($pointer),
                $subURI,
                null,
                $this->root
            );
        }
        printf("\nGot old sub for %s\n", $this->root->subCache[$pointer]->uri);

        return $this->root->subCache[$pointer];
    }

    /**
     * Validate a document
     *
     * @param ValueHelper $document
     */
    public function validate(ValueHelper $document)
    {
        // handle boolean schemas
        if ($this->spec->standard('allowBooleanSchema')) {
            $documentValue = $document->getValue();
            if ($documentValue === true) {
                return;
            } elseif ($documentValue === false) {
                throw Exception::BOOLEAN_SCHEMA_FALSE();
            }
        }

        // dereference schema & validate against target
        if ($this->hasMember('$ref')) {
            printf("\nDereference %s against %s\n", $this->getMemberValue('$ref'), $this->uri);
            $ref = $this->getMemberValue('$ref');
            if ($ref == '#') {
                $schema = $this->root;
            } else {
                if (substr($ref, 0, 1) == '#') {
                    $schema = $this->getSub($ref);
                } else {
                    $schema = $this->state->getSchema(Util::clampURI($ref, $this->getURI()));
                }
            }
            if ($schema !== $this) {
                return $schema->validate($document);
            }
        }

        // run handlers
        $this->each(function (ValueHelper $definition, $keyword) use ($document) {
            // 'default' is a metadata keyword, so needs to be explicitly included in order for the handler to run
            if ($this->spec->validation($keyword) || $keyword == 'default' && $this->spec->metadata($keyword)) {
                $handler = $this->state->getHandler($keyword);
                $forTypes = $handler->forTypes();
                if ($document->isDefined() && (!count($forTypes) || $document->isTypes(...$forTypes))) {
                    $handler->run($keyword, $document, $this, $definition);
                } elseif (!$document->isDefined() && $handler->forUndefined()) {
                    $handler->run($keyword, $document, $this, $definition);
                }
            }
        }, 'default');
    }
}
