<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\SchemaHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "disallow"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class DisallowHandler extends BaseHandler
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
        $spec = $schema->getMeta('schema')->getSpec();

        if (!is_array($definition)) {
            $definition = [$definition];
        }

        foreach ($definition as $typeDefinition) {
            if ($typeDefinition instanceof SchemaHelper && $spec->rule('allowSchemaInUnionType')) {
                // schema types
                $matchedType = false;
                try {
                    $this->state->getValidator()->validateInstance($document, $typeDefinition);
                    $matchedType = true;
                } catch (\Exception $e) {
                    // ignore exceptions for failed types
                }
                if ($matchedType) {
                    throw ValidationException::IS_ILLEGAL_TYPE('schema type match');
                }
            } else {
                // string types
                if ($document->isType($typeDefinition)) {
                    throw ValidationException::IS_ILLEGAL_TYPE($typeDefinition);
                }
            }
        }
    }
}
