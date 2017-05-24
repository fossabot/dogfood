<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Base handler class
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
abstract class BaseHandler extends BaseInstance
{
    /** @var bool Whether to run handler for undefined values */
    protected $forUndefined = false;

    /**
     * Run validation against a document
     *
     * @param string $keyword
     * @param ValueHelper $document
     * @param Schema $schema
     * @param mixed $definition
     */
    abstract public function run(string $keyword, ValueHelper $document, Schema $schema, $definition);

    /**
     * Whether this handler should run for undefined values
     *
     * @return bool
     */
    final public function forUndefined() : bool
    {
        return $this->forUndefined;
    }
}
