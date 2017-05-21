<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "oneOf"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class OneOfHandler extends BaseHandler
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
        $validCount = 0;
        $definition->each(function ($subDefinition) use ($document, $schema, &$validCount) {
            try {
                $schema->getSub(null, $subDefinition)->validate($document);
                $validCount++;
            } catch (\Throwable $e) {
                // don't care about failures, because we want only one subschema to pass
            }

            if ($validCount > 1) {
                throw Exception::ONE_OF_TOO_MANY_MATCHES();
            }
        });

        // failed all the schemas, so complain about that
        if (!$validCount) {
            throw Exception::ONE_OF_NO_MATCHES();
        }
    }
}
