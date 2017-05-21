<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "anyOf"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class AnyOfHandler extends BaseHandler
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
        $subSchemas = [];
        $definition->each(function ($subDefinition) use ($document, $schema, &$subSchemas) {
            $subSchemas[] = $schema->getSub(null, $subDefinition);
        });

        foreach ($subSchemas as $subSchema) {
            try {
                $subSchema->validate($document);
                return;
            } catch (\Throwable $e) {
                // don't care about failures, because we only need one subschema to pass
            }
        }

        // failed all the schemas, so complain about that
        throw Exception::ANY_OF_FAIL();
    }
}
