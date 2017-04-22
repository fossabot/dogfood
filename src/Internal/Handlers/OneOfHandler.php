<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\SchemaHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "oneOf"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class OneOfHandler extends BaseHandler
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
        $matches = 0;
        foreach ($definition as $oneOfSchema) {
            try {
                $this->state->getValidator()->validateInstance($document, $oneOfSchema);
                $matches++;
                if ($matches > 1) {
                    break;
                }
            } catch (\Exception $e) {
                // squash exceptions from non-passing schemas, as we only want one to pass
            }
        }

        // no matches found
        if ($matches != 1) {
            throw ValidationException::ONE_OF_NO_OR_EXTRA_MATCH();
        }
    }
}
