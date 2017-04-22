<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Internal\State;
use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\SchemaHelper;

/**
 * Base handler class
 *
 * @package erayd/dogfood
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
abstract class BaseHandler
{
    /** @var State Internal state */
    protected $state = null;

    /** @var bool Whether to process undefined values */
    protected $processUndefined = false;

    /**
     * Create a new handler instance
     *
     * @param State $state
     */
    public function __construct(State $state)
    {
        $this->state = $state;
    }

    /**
     * Check whether a handler wants to process undefined instances
     *
     * @return bool
     */
    public function willProcessUndefined() : bool
    {
        return $this->processUndefined;
    }

    /**
     * Check whether a keyword is present in a definition and available in the spec
     *
     * @param string $keyword
     * @return bool
     */
    protected function shouldProcessKeyword(string $keyword, SchemaHelper $definition) : bool
    {
        $spec = $definition->getMeta('schema')->getSpec();
        return $definition->hasProperty($keyword) && $spec->keyword($keyword);
    }

    /**
     * Run validation
     *
     * @param ValueHelper $document
     * @param SchemaHelper $schema
     * @param mixed $definition
     * @param string $keyword
     */
    abstract public function run(ValueHelper $document, SchemaHelper $schema, $definition, string $keyword);
}
