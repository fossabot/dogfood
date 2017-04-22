<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\State;
use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\SchemaHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "items"
 *  "additionalItems"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class ItemsHandler extends BaseHandler
{
    /**
     * Run validation
     *
     * @param ValueHelper $document
     * @param SchemaHelper $schema
     * @param mixed $definition
     * @param string $keyword
     */
    public function run(ValueHelper $document, SchemaHelper $schema, $definition, string $keyword)
    {
        // don't process "additionalItems" twice
        if ($keyword == 'additionalItems') {
            return;
        }

        // only applicable to arrays
        if (!$document->isArray()) {
            return;
        }

        // count array elements
        $documentCount = count($document->getValue());

        if (is_object($definition)) {
            // fill definition
            $definition = array_fill(0, $documentCount, $definition);
        } elseif ($documentCount > count($definition) && $this->shouldProcessKeyword('additionalItems', $schema)) {
            // check additionalItems
            $additionalItems = new ValueHelper($this->state, $schema->getProperty('additionalItems'));
            if ($additionalItems->isObject()) {
                $definition = array_pad($definition, $documentCount, $additionalItems->getValue());
            } elseif ($additionalItems->isBoolean() && !$additionalItems->getValue()) {
                throw ValidationException::ADDITIONAL_ITEMS_FORBIDDEN();
            }
        }

        // validate array items
        foreach ($definition as $key => $itemDefinition) {
            $member = ValueHelper::createForMember($this->state, $document->getValue(), $key);
            $this->state->getValidator()->validateInstance($member, $itemDefinition);
        }
    }
}
