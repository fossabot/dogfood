<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "dependencies"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class DependenciesHandler extends BaseHandler
{
    /**
     * Run validation against a document
     *
     * @param string $keyword
     * @param ValueHelper $document
     * @param Schema $schema
     * @param mixed $definition
     */
    public function run(string $keyword, ValueHelper $document, Schema $schema, $definition)
    {
        // validate dependencies
        $definition->each(function ($dependency, $propertyName) use ($document, $schema) {
            // if this property isn't present, no need to check its dependencies
            if (!$document->hasMember($propertyName)) {
                return;
            }

            if ($dependency->isTypes('array', 'string')) {
                $requiredProperties = $dependency->getValue();

                // check for simple dependencies & wrap into array
                if (!is_array($requiredProperties)) {
                    if (!$schema->getSpec()->implementation->allowSimpleDependencies) {
                        throw Exception::SIMPLE_DEPENDENCIES_NOT_ALLOWED();
                    }
                    $requiredProperties = [$requiredProperties];
                }

                // check dependencies
                foreach ($requiredProperties as $requiredName) {
                    if (!$document->hasMember($requiredName)) {
                        throw Exception::REQUIRED_PROPERTY_MISSING($requiredName);
                    }
                }
            } else {
                // schema dependencies
                $dependencySchema = $schema->getSub(null, $dependency);
                $dependencySchema->validate($document);
            }
        });
    }
}
