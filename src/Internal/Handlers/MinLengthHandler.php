<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "minLength"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class MinLengthHandler extends BaseHandler
{
    /** @var string[] Which types to process */
    protected $forTypes = ['string'];

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
        $documentLength = mb_strlen($document->getValue());
        $minLength = $definition->getValue();

        if ($documentLength < $minLength) {
            throw Exception::STRING_TOO_SHORT($minLength, $documentLength);
        }
    }
}
