<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\State;
use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\ObjectHelper;
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
     * @param ObjectHelper $schema
     * @param mixed $definition
     * @param string $keyword
     */
    public function run(ValueHelper $document, ObjectHelper $schema, $definition, string $keyword)
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

        // fill definition
        if (is_object($definition)) {
            $definition = array_fill(0, $documentCount, $definition);
        }

        // check additionalItems
        if ($documentCount > count($definition) && $this->shouldProcessKeyword('additionalItems', $schema)) {
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
