<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "multipleOf"
 *  "divisibleBy"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class MultipleOfHandler extends BaseHandler
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
        $dividend = $document->getValue();
        $divisor = $definition->getValue();
        $quotient = $dividend / $divisor;
        if ($quotient != floor($quotient)) {
            throw Exception::NOT_MULTIPLE_OF($dividend, $divisor);
        }
    }
}
