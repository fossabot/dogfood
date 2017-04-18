<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\ObjectHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "maximum"
 *  "exclusiveMaximum"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class MaximumHandler extends BaseHandler
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
        // don't process exclusiveMaximum independently
        if ($keyword == 'exclusiveMaximum') {
            return;
        }

        // only applicable to numbers
        if (!$document->isNumber()) {
            return;
        }

        // handle exclusiveMaximum
        $exclusive = $this->shouldProcessKeyword('exclusiveMaximum', $schema) && $schema->exclusiveMaximum;

        // check maximum
        if ($document->getValue() > $definition || ($exclusive && $document->getValue() == $definition)) {
            throw ValidationException::NUMBER_GREATER_THAN_MAXIMUM();
        }
    }
}
