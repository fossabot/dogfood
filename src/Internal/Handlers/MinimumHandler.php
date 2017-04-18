<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\ObjectHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "minimum"
 *  "exclusiveMinimum"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class MinimumHandler extends BaseHandler
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
        // don't process exclusiveMinimum independently
        if ($keyword == 'exclusiveMinimum') {
            return;
        }

        // only applicable to numbers
        if (!$document->isNumber()) {
            return;
        }

        // handle exclusiveMinimum
        $exclusive = $this->shouldProcessKeyword('exclusiveMinimum', $schema) && $schema->exclusiveMinimum;

        // check minimum
        if ($document->getValue() < $definition || ($exclusive && $document->getValue() == $definition)) {
            throw ValidationException::NUMBER_LOWER_THAN_MINIMUM();
        }
    }
}
