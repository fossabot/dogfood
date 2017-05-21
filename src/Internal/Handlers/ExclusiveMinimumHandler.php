<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "exclusiveMinimum"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class ExclusiveMinimumHandler extends BaseHandler
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
        // only for non-boolean exclusive
        if (!$schema->getSpec()->standard('exclusiveMinMaxIsNumber')) {
            return;
        }

        $number = $document->getValue();
        $minimum = $definition->getValue();

        if ($number <= $minimum) {
            throw Exception::NUMBER_LESS_THAN_EX_MINIMUM($minimum);
        }
    }
}
