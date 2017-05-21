<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "maximum"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class MaximumHandler extends BaseHandler
{
    /** @var string[] Which types to process */
    protected $forTypes = ['number'];

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
        $number = $document->getValue();
        $maximum = $definition->getValue();

        if ($schema->hasMember('exclusiveMaximum') && !$schema->getSpec()->standard('exclusiveMinMaxIsNumber')
            && $schema->getMemberValue('exclusiveMaximum') === true
        ) {
            if ($number >= $maximum) {
                throw Exception::NUMBER_GREATER_THAN_EX_MAXIMUM($maximum);
            }
        }

        if ($number > $maximum) {
            throw Exception::NUMBER_GREATER_THAN_MAXIMUM($maximum);
        }
    }
}
