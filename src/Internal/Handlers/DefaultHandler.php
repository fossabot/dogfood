<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Validator;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\SchemaHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "default"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class DefaultHandler extends BaseHandler
{
    /** @var bool Process undefined instances */
    protected $processUndefined = true;

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
        if (!$document->isDefined() && $this->state->getOption(Validator::OPT_APPLY_DEFAULTS)) {
            $document->setValue($definition);
        }
    }
}
