<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\SchemaHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "maxLength"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class MaxLengthHandler extends BaseHandler
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
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }

        // check length
        if (mb_strlen($document->getValue()) > $definition) {
            throw ValidationException::STRING_LONGER_THAN_MAX_LENGTH($definition);
        }
    }
}
