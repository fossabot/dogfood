<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\SchemaHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "uniqueItems"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class UniqueItemsHandler extends BaseHandler
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
        // only applicable to arrays
        if (!$document->isArray()) {
            return;
        }

        // Cannot use array_unique(), because strict-type comparisons are required for !is_object().
        // Cannot use in_array($value, $seen, !is_object($value)) either, because it will attempt to
        // internally perform an invalid cast on object instances of $value in some cases. Therefore,
        // the solution is to check uniqueness separately for objects vs any other type of value.
       
        // check uniqueness
        if ($definition) {
            $seen = $seenObject = [];
            $document->each(function (&$value) use (&$seen, &$seenObject) {
                if (is_object($value)) {
                    if (in_array($value, $seenObject, false)) {
                        throw ValidationException::NON_UNIQUE_ARRAY();
                    }
                    $seenObject[] = $value;
                } else {
                    if (in_array($value, $seen, true)) {
                        throw ValidationException::NON_UNIQUE_ARRAY();
                    }
                    $seen[] = &$value;
                }
            });
        }
    }
}
