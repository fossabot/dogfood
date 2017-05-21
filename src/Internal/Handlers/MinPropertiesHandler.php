<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "minProperties"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class MinPropertiesHandler extends BaseHandler
{
    /** @var string[] Which types to process */
    protected $forTypes = ['object'];

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
        $propertyCount = $document->count();
        $propertyMin = $definition->getValue();

        if ($propertyCount < $propertyMin) {
            throw Exception::NOT_ENOUGH_PROPERTIES($propertyMin, $propertyCount);
        }
    }
}
