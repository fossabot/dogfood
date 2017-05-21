<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Apply default values to undefined instances
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class DefaultHandler extends BaseHandler
{
    /** @var bool Whether to run handler for undefined values */
    protected $forUndefined = true;

    /**
     * Run validation against a document
     *
     * @param string $keyword
     * @param ValueHelper $document
     * @param Schema $schema
     * @param mixed $definition
     */
    public function run(string $keyword, ValueHelper $document, Schema $schema, $definition)
    {
        if ($this->state->getOption(Config::APPLY_DEFAULTS) && !$document->isDefined()) {
            $defaultValue = $definition->getValue();
            $document->setValue(is_object($defaultValue) ? clone $defaultValue : $defaultValue);
        }
    }
}
