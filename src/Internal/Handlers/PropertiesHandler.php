<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\State;
use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\ObjectHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "properties"
 *  "patternProperties"
 *  "additionalProperties"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class PropertiesHandler extends BaseHandler
{
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
        // only applicable to objects
        if (!$document->isObject()) {
            return;
        }

        // handle delegations
        if ($keyword != 'properties') {
            if ($schema->hasProperty('properties')) {
                return;
            } elseif ($keyword == 'additionalProperties' && $schema->hasProperty('patternProperties')) {
                return;
            } else {
                $definition = new ObjectHelper(new \StdClass(), $schema->getOptions(), $schema->getPath() . '/properties');
            }
        }

        // get list of document properties
        $documentProperties = [];
        $document->each(function (&$value, $propertyName) use (&$documentProperties) {
            $documentProperties[$propertyName] = false;
        });

        // validate properties
        $definition->each(function (&$definition, $propertyName) use ($document, &$documentProperties) {
            // get & validate value
            $value = ValueHelper::createForMember($this->state, $document->getValue(), $propertyName);
            $this->state->getValidator()->validateInstance($value, $definition, $document->hasMember($propertyName));
            $documentProperties[$propertyName] = true;
        });

        // validate patternProperties
        if ($this->shouldProcessKeyword('patternProperties', $schema)) {
            // iterate patterns
            $schema->patternProperties->each(function ($definition, $pattern) use ($document, &$documentProperties) {
                $pattern = '/' . str_replace('/', '\/', $pattern) . '/u';
                // iterate properties
                $document->each(function ($ignored, $propertyName) use ($pattern, $document, $definition, &$documentProperties) {
                    if (preg_match($pattern, $propertyName)) {
                        $value = ValueHelper::createForMember($this->state, $document->getValue(), $propertyName);
                        $this->state->getValidator()->validateInstance($value, $definition);
                        $documentProperties[$propertyName] = true;
                    }
                });
            });
        }

        // validate additionalProperties
        if ($this->shouldProcessKeyword('additionalProperties', $schema)) {
            $documentProperties = array_filter($documentProperties, function ($checked) {
                return !$checked;
            });
            if (count($documentProperties)) {
                $additionalProperties = new ValueHelper($this->state, $schema->additionalProperties);
                if ($additionalProperties->isObject()) {
                    // validate additional properties
                    foreach ($documentProperties as $propertyName => $checked) {
                        $value = ValueHelper::createForMember($this->state, $document->getValue(), $propertyName);
                        $this->state->getValidator()->validateInstance($value, $additionalProperties->getValue());
                    }
                } elseif ($additionalProperties->isBoolean() && !$additionalProperties->getValue()) {
                    // additional properties are forbidden
                    throw ValidationException::ADDITIONAL_PROPERTIES_FORBIDDEN();
                }
            }
        }
    }
}
