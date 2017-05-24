<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "minimum"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class MinimumHandler extends BaseHandler
{
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
        $minimum = $definition->getValue();

        if ($schema->hasMember('exclusiveMinimum')
            && in_array('boolean', $schema->getSpec()->validation->exclusiveMinimum->{'allow-types'})
            && $schema->getMemberValue('exclusiveMinimum') === true
        ) {
            if ($number <= $minimum) {
                throw Exception::NUMBER_LESS_THAN_EX_MINIMUM($minimum);
            }
        }

        if ($number < $minimum) {
            throw Exception::NUMBER_LESS_THAN_MINIMUM($minimum);
        }
    }
}
