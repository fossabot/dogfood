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
 *  "pattern"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class PatternHandler extends BaseHandler
{
    /** @var string[] Which types to process */
    protected $forTypes = ['string'];

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
        $pattern = $definition->getValue();
        $pcre = Util::patternToPCRE($pattern);

        if (!preg_match($pcre, $document->getValue())) {
            throw Exception::PATTERN_MISMATCH($pattern);
        }
    }
}
