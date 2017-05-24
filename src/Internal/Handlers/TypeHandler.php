<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "type"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class TypeHandler extends BaseHandler
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
        if ($definition->isString() && !$document->isType($definition->getValue())) {
            // simple string type
            throw Exception::INVALID_TYPE($document->getType());
        } elseif ($definition->isArray()) {
            // array types
            $isValid = false;
            $definition->each(function ($definition) use ($document, $schema, &$isValid) {
                // don't process further types if we've already found a match
                if ($isValid) {
                    return;
                }

                if ($definition->isString()) {
                    // string types
                    if ($document->isType($definition->getValue())) {
                        $isValid = true;
                    }
                } elseif ($schema->getSpec()->implementation->allowSchemaInUnionType) {
                    // schema types
                    $typeSchema = $schema->getSub(null, $definition);
                    try {
                        $typeSchema->validate($document);
                        $isValid = true;
                    } catch (\Throwable $e) {
                        // we don't care about failures here, because this is an array type
                    }
                }
            });

            // no valid types were found
            if (!$isValid) {
                throw Exception::INVALID_TYPE($document->getType());
            }
        }
    }
}
