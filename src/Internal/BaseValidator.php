<?php

namespace Dogfood\Internal;

use Erayd\JsonSchemaInfo\SchemaInfo;

use Dogfood\Exception\RuntimeException;
use Dogfood\Validator;

/**
 * Validation interface
 *
 * @package erayd/dogfood
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class BaseValidator extends Schema
{
    /** @var array Internally-handled or ignored keywords */
    private $internalKeywords = ['$schema', 'id', 'description', 'title', 'definitions'];

    /** @var array Priority keywords */
    private $priorityKeywords = ['default'];

    /**
     * Create a new Validator instance
     *
     * @param State $state
     * @param \StdClass $schema
     * @param string $uri
     */
    public function __construct(State $state, \StdClass $schema = null, string $uri = null)
    {
        // call parent constructor
        $specVersion = $state->getOption(Validator::OPT_STANDARD);
        $state->setValidator($this);
        parent::__construct($state, $uri, $schema, $specVersion ? new SchemaInfo($specVersion) : null);
    }

    /**
     * Run validation handlers against a document instance
     *
     * @param ValueHelper $document
     * @param ObjectHelper $definition
     */
    public function validateInstance(ValueHelper $document, ObjectHelper $definition)
    {
        // dereference definition
        $definition = $definition->getMeta('schema')->dereference($definition);

        // run priority handlers
        foreach ($this->priorityKeywords as $keyword) {
            if ($definition->hasProperty($keyword)) {
                $this->runHandler($keyword, $document, $definition);
            }
        }

        // run remaining handlers
        $definition->each(function ($value, $keyword) use ($document, $definition) {
            if (!in_array($keyword, $this->priorityKeywords)) {
                $this->runHandler($keyword, $document, $definition);
            }
        });
    }

    /**
     * Run the helper for the specified keyword
     *
     * @param string $keyword
     * @param ValueHelper $document
     * @param ObjectHelper $definition
     */
    private function runHandler(string $keyword, ValueHelper $document, ObjectHelper $definition)
    {
        // skip keywords which are handled internally already
        if (in_array($keyword, $this->internalKeywords)) {
            return;
        }

        // get definition schema object
        $schema = $definition->getMeta('schema');

        // process keyword
        try {
            // validate keyword
            if ($schema->spec->keyword($keyword)) {
                $handler = $this->state->getHandler($keyword);
                // run handler, unless $document is undefined and $handler doesn't want undefined instances
                if ($document->isDefined() || $handler->willProcessUndefined()) {
                    $handler->run($document, $definition, $definition->$keyword, $keyword);
                }
            }
        } catch (\InvalidArgumentException $e) {
            // ignore invalid keywords
            return;
        }
    }
}
