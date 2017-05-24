<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\Util;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "properties"
 *  "patternProperties"
 *  "additionalProperties"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class PropertiesHandler extends BaseHandler
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
        // don't process 'patternProperties' independently unless 'properties' is missing
        if ($keyword == 'patternProperties' && $schema->hasMember('properties')) {
            return;
        }

        // don't process 'additionalProperties' independently unless
        // 'properties' and 'patternProperties' are both missing.
        if ($keyword == 'additionalProperties'
            && ($schema->hasMember('properties') || $schema->hasMember('patternProperties'))) {
            return;
        }

        $validatedProperties = [];

        // process 'properties'
        if ($schema->hasMember('properties')) {
            $schema->getMember('properties')->each(
                function ($propertyDefinition, $propertyName) use ($document, $schema, &$validatedProperties) {
                    $propertySchema = $schema->getSub(null, $propertyDefinition);
                    $propertySchema->validate($document->getMember($propertyName));
                    $validatedProperties[] = $propertyName;
                }
            );
        }

        // process 'patternProperties'
        if ($schema->hasMember('patternProperties')) {
            $schema->getMember('patternProperties')->each(
                function ($patternDefinition, $pattern) use ($document, $schema, &$validatedProperties) {
                    $patternSchema = $schema->getSub(null, $patternDefinition);
                    $pattern = Util::patternToPCRE($pattern);
                    $document->each(
                        function ($property, $propertyName) use ($pattern, $patternSchema, &$validatedProperties) {
                            if (!preg_match($pattern, $propertyName)) {
                                return;
                            }
                            $patternSchema->validate($property);
                            $validatedProperties[] = $propertyName;
                        }
                    );
                }
            );
        }

        // process 'additionalProperties'
        if ($schema->hasMember('additionalProperties')) {
            $additionalProperties = $schema->getMember('additionalProperties');
            if ($additionalProperties->getValue() === false) {
                // complain if any property isn't listed as already validated
                $document->each(function ($propertyValue, $propertyName) use ($validatedProperties) {
                    if (!in_array($propertyName, $validatedProperties)) {
                        throw Exception::ADDITIONAL_PROPERTIES_PROHIBITED();
                    }
                });
            } elseif ($additionalProperties->isObject()) {
                $additionalSchema = $schema->getSub(null, $additionalProperties);
                // validate remaining properties against additional schema
                $document->each(
                    function ($propertyHelper, $propertyName) use ($validatedProperties, $additionalSchema) {
                        if (!in_array($propertyName, $validatedProperties)) {
                            $additionalSchema->validate($propertyHelper);
                        }
                    }
                );
            }
        }
    }
}
