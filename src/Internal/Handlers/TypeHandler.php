<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\State;
use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\SchemaHelper;

/**
 * Handler for the following keywords:
 *  "type"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class TypeHandler extends BaseHandler
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
                try {
                    $this->state->getValidator()->validateInstance($document, $typeDefinition);
                    return;
                } catch (\Exception $e) {
                    // ignore exceptions for failed types
                }
            } else {
                // string types
                if (!$spec->type($typeDefinition)) {
                    continue;
                }
                if ($document->isType($typeDefinition) || $typeDefinition == 'any') {
                    return;
                }
            }
        }

        // type check failed
        throw ValidationException::IS_ILLEGAL_TYPE($document->getType());
    }
}
