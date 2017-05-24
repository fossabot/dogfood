<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "disallow"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class DisallowHandler extends BaseHandler
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
        if ($definition->isString() && $document->isType($definition->getValue())) {
            // simple string type
            throw Exception::INVALID_TYPE($document->getType());
        } elseif ($definition->isArray()) {
            // array types
            $definition->each(function (ValueHelper $definition) use ($document, $schema) {
                if ($definition->isString()) {
                    // string types
                    if ($document->isType($definition->getValue())) {
                        throw Exception::INVALID_TYPE($document->getType());
                    }
                } elseif ($schema->getSpec()->implementation->allowSchemaInUnionType) {
                    // schema types
                    $typeSchema = $schema->getSub(null, $definition);
                    try {
                        $typeSchema->validate($document);
                        $isValid = true;
                    } catch (\Throwable $e) {
                        $isValid = false;
                    }
                    if ($isValid) {
                        throw Exception::INVALID_TYPE($document->getType());
                    }
                }
            });
        }
    }
}
