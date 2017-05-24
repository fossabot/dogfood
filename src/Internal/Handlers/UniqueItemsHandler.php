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
 *  "uniqueItems"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class UniqueItemsHandler extends BaseHandler
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
        if ($definition->getValue()) {
            $seen = [];
            $document->each(function ($item) use (&$seen) {
                foreach ($seen as $seenItem) {
                    if (Parity::isEqualTo($item->getValue(), $seenItem->getValue())) {
                        throw Exception::NON_UNIQUE_ARRAY();
                    }
                }
                $seen[] = $item;
            });
        }
    }
}
