<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "items"
 *  "additionalItems"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class ItemsHandler extends BaseHandler
{
    /** @var string[] Which types to process */
    protected $forTypes = ['array'];

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
        // don't process "additionalItems" independently
        if ($keyword == 'additionalItems') {
            return;
        }

        $itemCount = $document->count();

        if ($definition->isArray()) {
            // fill check array
            $itemCheck = [];
            $definition->each(function ($value) use (&$itemCheck, $schema) {
                $itemCheck[] = $schema->getSub(null, $value);
            });

            // handle additional items
            if (count($itemCheck) < $itemCount && $schema->hasMember('additionalItems')) {
                $additionalItems = $schema->getMember('additionalItems');
                if ($additionalItems->getValue() === false) {
                    throw Exception::ADDITIONAL_ITEMS_PROHIBITED();
                } elseif ($additionalItems->isObject()) {
                    $additionalSchema = $schema->getSub(null, $additionalItems);
                    for ($i = count($itemCheck); $i < $itemCount; $i++) {
                        $itemCheck[] = $additionalSchema;
                    }
                }
            }

            // run check array
            foreach ($itemCheck as $item => $itemSchema) {
                if ($document->hasMember($item)) {
                    $itemSchema->validate($document->getMember($item));
                } else {
                    break;
                }
            }
        } elseif ($definition->isObject()) {
            // the definition is a schema, so check all items against it
            $itemSchema = $schema->getSub(null, $definition);
            $document->each(function (ValueHelper $member) use ($itemSchema) {
                $itemSchema->validate($member);
            });
        }
    }
}
