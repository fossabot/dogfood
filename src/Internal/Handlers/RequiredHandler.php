<?php

namespace JsonValidator\Internal\Handlers;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "required"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class RequiredHandler extends BaseHandler
{
    /** @var bool Whether to run handler for undefined values */
    protected $forUndefined = true;

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
        if (in_array('boolean', $schema->getSpec()->validation->required->{'allow-types'})) {
            // v3 boolean required
            if ($definition->getValue() && !$document->isDefined()) {
                $path = $document->getPath();
                throw Exception::REQUIRED_PROPERTY_MISSING(end($path));
            }
        } elseif ($document->isDefined()) {
            // v4+ array-style required
            foreach ($definition->getValue() as $requiredProperty) {
                if (!$document->hasMember($requiredProperty)) {
                    throw Exception::REQUIRED_PROPERTY_MISSING($requiredProperty);
                }
            }
        }
    }
}
