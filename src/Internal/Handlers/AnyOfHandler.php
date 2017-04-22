<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\SchemaHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "anyOf"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class AnyOfHandler extends BaseHandler
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
        foreach ($definition as $anyOfSchema) {
            try {
                $this->state->getValidator()->validateInstance($document, $anyOfSchema);
                return;
            } catch (\Exception $e) {
                // squash exceptions from non-passing schemas, as we only need one to pass
            }
        }

        throw ValidationException::ANY_OF_NO_MATCH();
    }
}
