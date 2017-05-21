<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "extends"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class ExtendsHandler extends BaseHandler
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
        if ($definition->isArray()) {
            $definition->each(function ($definition) use ($document, $schema) {
                $extendSchema = $schema->getSub(null, $definition);
                $extendSchema->validate($document);
            });
        } else {
            $extendSchema = $schema->getSub(null, $definition);
            $extendSchema->validate($document);
        }
    }
}
