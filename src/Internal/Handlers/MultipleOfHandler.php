<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\ObjectHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "multipleOf"
 *  "divisibleBy"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class MultipleOfHandler extends BaseHandler
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
        // only applicable to numbers
        if (!$document->isNumber()) {
            return;
        }

        // check multiple
        $quotient = $document->getValue() / $definition;
        if (floor($quotient) != $quotient) {
            throw ValidationException::NOT_MULTIPLE_OF($document->getValue(), $definition);
        }
    }
}
