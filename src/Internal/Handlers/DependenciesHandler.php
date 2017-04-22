<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\SchemaHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "dependencies"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class DependenciesHandler extends BaseHandler
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
        // only applicable to objects
        if (!$document->isObject()) {
            return;
        }

        // get spec
        $spec = $definition->getMeta('schema')->getSpec();

        // iterate dependencies
        $definition->each(function ($depends, $propertyName) use ($document, $spec) {
            // dependencies don't matter if the property isn't defined
            if (!$document->hasMember($propertyName)) {
                return;
            }

            if (is_string($depends)) {
                // draft-03 simple dependencies
                if (!$spec->rule('simpleDependencies')) {
                    throw SchemaException::NO_SIMPLE_DEPENDENCIES();
                }
                if (!$document->hasMember($depends)) {
                    throw ValidationException::MISSING_DEPENDENCY($depends);
                }
            } elseif (is_array($depends)) {
                // array of simple dependencies
                foreach ($depends as $dependsProperty) {
                    if (!is_string($dependsProperty)) {
                        throw SchemaException::NON_STRING_ARRAY_DEPENDENCY();
                    }
                    if (!$document->hasMember($dependsProperty)) {
                        throw ValidationException::MISSING_DEPENDENCY($dependsProperty);
                    }
                }
            } elseif ($depends instanceof SchemaHelper) {
                // schema dependency
                $this->state->getValidator()->validateInstance($document, $depends);
            }
        });
    }
}
