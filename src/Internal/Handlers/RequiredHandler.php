<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\ObjectHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "required"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class RequiredHandler extends BaseHandler
{
    /** @var bool Process undefined instances */
    protected $processUndefined = true;

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
        $spec = $schema->getMeta('schema')->getSpec();

        if (is_bool($definition) && $spec->rule('requiredBoolean') && $definition && !$document->isDefined()) {
            // v3 boolean-type required
            throw ValidationException::MISSING_REQUIRED($document->getKey());
        } elseif ($document->isDefined() && is_array($definition)) {
            // v4+ array-type required
            foreach ($definition as $require) {
                if (!$document->hasMember($require)) {
                    throw ValidationException::MISSING_REQUIRED($require);
                }
            }
        }
    }
}
