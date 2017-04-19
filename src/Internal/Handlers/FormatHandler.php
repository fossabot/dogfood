<?php

namespace Dogfood\Internal\Handlers;

use Dogfood\Exception\ValidationException;

use Dogfood\Internal\ValueHelper;
use Dogfood\Internal\ObjectHelper;
use Dogfood\Internal\BaseValidator;

/**
 * Handler for the following keywords:
 *  "format"
 *
 *  @package erayd/dogfood
 *  @copyright (c) 2017 Erayd LTD
 *  @author Steve Gilberd <steve@erayd.net>
 */
class FormatHandler extends BaseHandler
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
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }

        switch ($definition) {
            case 'regex':
                if ($schema->getMeta('schema')->getSpec()->format($definition)) {
                    $this->{'format' . ucfirst($definition)}($document->getValue());
                } else {
                    // TODO hook for custom format handler (official in another spec version)
                }
                break;
            default:
                // TODO hook for custom format handler (unknown format)
        }
    }

    /**
     * Check regex format
     *
     * @param string $value
     */
    private function formatRegex(string $value)
    {
        $pattern = ValueHelper::patternToPCRE($value);

        // check that the expression compiles
        set_error_handler(function() use($value) {
            throw ValidationException::INVALID_REGEX($value);
            restore_error_handler();
        });
        preg_match($pattern, '');
        restore_error_handler();

        // check for lookbehind, because ECMA-262 doesn't support it
        if (preg_match('/(\(\?<(?:[^()]++|(?1))*\))/', $value)) {
            throw ValidationException::UNSUPPORTED_LOOKBEHIND($value);
        }

    }
}
