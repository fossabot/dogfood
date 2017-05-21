<?php

namespace JsonValidator\Internal\Handlers;

use Icecave\Parity\Parity;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "enum"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class EnumHandler extends BaseHandler
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
        $documentValue = $document->getValue();
        foreach ($definition->getValue() as $enumDefinition) {
            if (Parity::isEqualTo($documentValue, $enumDefinition)) {
                return;
            }
        }

        throw Exception::NOT_IN_ENUM();
    }
}
