<?php

namespace JsonValidator\Internal\Handlers;

use Sabberworm\CSS\Parser as CSSParser;
use Sabberworm\CSS\Settings as CSSSettings;

use JsonValidator\Exception;

use JsonValidator\Config;
use JsonValidator\Internal\BaseInstance;
use JsonValidator\Internal\Schema;
use JsonValidator\Internal\Util;
use JsonValidator\Internal\ValueHelper;

/**
 * Handler for the following keywords:
 *  "format"
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class FormatHandler extends BaseHandler
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
        // the following pre-defined formats are deliberately not validated:
        //  - phone  (format specification is vague, and only says MAY follow E.123)
        //  - uriref (pending resolution of json-schema-spec issue #310)

        // validate pre-defined formats
        $format = $definition->getValue();
        switch ($format) {
            case 'ip-address':
            case 'ipv4':
            case 'ipv6':
            case 'host-name': // PHP method names are not case-sensitive, so no alias is needed
            case 'hostname':
            case 'email':
            case 'regex':
            case 'date-time':
            case 'date':
            case 'time':
            case 'color':
            case 'style':
            case 'uri':
            case 'uriref':
            case 'uri-reference':
                if ($schema->getSpec()->format($format)) {
                    $formatMethod = preg_replace_callback(
                        '/-([^-]+)/',
                        function ($matches) {
                            return ucfirst($matches[1]);
                        },
                        $format
                    );
                    $this->{'format' . ucfirst($formatMethod)}($document, $schema);
                } else {
                    // workaround for invalid draft-04 test
                    // see https://github.com/json-schema-org/JSON-Schema-Test-Suite/issues/178
                    if ($format == 'regex' && $document->getValue() == '^\\S(|(.|\\n)*\\S)\\Z') {
                        throw Exception::TEST_SUITE_BUG(178);
                    }

                    // TODO hook for custom format handler (official in another spec version)
                }
                break;
            case 'bugfix-uri-ref':
                // workaround for buggy meta-schemas (draft-03, draft-04 & draft-05)
                // see https://github.com/json-schema-org/JSON-Schema-Test-Suite/issues/177#issuecomment-293051367
                $this->formatUriReference($document->getValue());
                break;
            default:
                // TODO hook for custom format handler (unknown format)
        }
    }

    /**
     * Alias for formatIpv4
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatIpAddress(ValueHelper $document, Schema $schema)
    {
        $this->formatIpv4($document, $schema);
    }

    /**
     * Check IPv4 format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatIpv4(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        if (filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) === false) {
            throw Exception::INVALID_IPV4($value);
        }
    }

    /**
     * Check IPv6 format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatIpv6(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        if (filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) === false) {
            throw Exception::INVALID_IPV6($value);
        }
    }

    /**
     * Check hostname format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatHostname(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        // remove trailing period
        if (substr($value, -1) == '.') {
            $value = substr($value, strlen($value) - 1);
        }

        // check total length
        if (strlen($value) > 253) {
            throw Exception::HOSTNAME_TOO_LONG($value);
        }

        $nodes = explode('.', $value);
        array_walk($nodes, function ($node) use ($value) {
            // check node length
            if (strlen($node) > 63) {
                throw Exception::HOSTNAME_COMPONENT_TOO_LONG($value);
            }
            // check node format
            if (!preg_match('/^(?:[a-z](?:-?[a-z0-9]+)?)?$/i', $node)) {
                throw Exception::INVALID_HOSTNAME_COMPONENT($node);
            }
        });
    }

    /**
     * Check email format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatEmail(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        $unicode = defined('\FILTER_FLAG_EMAIL_UNICODE') ? constant('\FILTER_FLAG_EMAIL_UNICODE') : 0;
        if (filter_var($value, \FILTER_VALIDATE_EMAIL, $unicode) === false) {
            throw Exception::INVALID_EMAIL($value);
        }
    }

    /**
     * Check regex format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatRegex(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        $pattern = Util::patternToPCRE($value);

        // check that the expression compiles
        set_error_handler(function () use ($value) {
            throw Exception::INVALID_REGEX($value);
            restore_error_handler();
        });
        preg_match($pattern, '');
        restore_error_handler();

        // check for lookbehind, because ECMA-262 doesn't support it
        if (preg_match('/(\(\?<(?:[^()]++|(?1))*\))/', $value)) {
            throw Exception::UNSUPPORTED_LOOKBEHIND($value);
        }

        // check for \Z anchor, because ECMA-262 doesn't support it
        if (preg_match('/(?<=^|[^\\\\Z])\\\\Z/', $value)) {
            throw Exception::UNSUPPORTED_Z_ANCHOR($value);
        }
    }

    /**
     * Check date-time format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatDateTime(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        // check date-time format
        $regex = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\.[0-9]+)?(?:Z|[+-][0-9]{2}:[0-9]{2})$/i';
        if (!preg_match($regex, $value, $matches)) {
            throw Exception::INVALID_DATETIME($value);
        }

        // check data is sane
        $format = 'Y-m-d\TH:i:s' . (isset($matches[1]) ? '.u' : '') . 'P';
        if (!\DateTime::createFromFormat($format, strtoupper($value))) {
            throw Exception::INVALID_DATETIME($value);
        }
    }

    /**
     * Check date format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatDate(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        // check date format
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)) {
            throw Exception::INVALID_DATE($value);
        }

        // check data is sane
        if (!\DateTime::createFromFormat('Y-m-d', $value)) {
            throw Exception::INVALID_DATE($value);
        }
    }

    /**
     * Check time format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatTime(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        // check date format
        if (!preg_match('/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $value)) {
            throw Exception::INVALID_TIME($value);
        }

        // check data is sane
        if (!\DateTime::createFromFormat('H:i:s', $value)) {
            throw Exception::INVALID_TIME($value);
        }
    }

    /**
     * Check CSS colour format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatColor(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        $colours = ['maroon', 'red', 'orange', 'yellow', 'olive', 'purple', 'fuchsia', 'white',
            'lime', 'green', 'navy', 'blue', 'aqua', 'teal', 'black', 'silver', 'gray'];

        if (preg_match('/^#[0-9a-f]{3}$/i', $value)) {
            // 3-digit hex | #369
        } elseif (preg_match('/^#[0-9a-f]{6}$/i', $value)) {
            // 6-digit hex | #336699
        } elseif (preg_match('/^rgb\(([0-9]+),([0-9]+),([0-9]+)\)$/', preg_replace('/\s+/', '', $value), $matches)) {
            // rgb-bracketed | rgb(51, 102, 153)
            if ($matches[1] > 255 || $matches[2] > 255 || $matches[3] > 255) {
                throw Exception::INVALID_CSS_COLOR($value);
            }
        } elseif (in_array(strtolower($value), $colours)) {
            // predefined css colour name
        } else {
            // no idea what this is, but it's not a valid colour
            throw Exception::INVALID_CSS_COLOR($value);
        }
    }

    /**
     * Check utc-millisec format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatUtcMillisec(ValueHelper $document, Schema $schema)
    {
        $value = $document->getValue();
        if (!$document->isNumber()) {
            throw Exception::INVALID_UTC_MILLISEC(is_scalar($value) ? $value : $document->getType());
        }
    }

    /**
     * Check style format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatStyle(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        $settings = CSSSettings::create()->beStrict();
        try {
            $parser = new CSSParser(sprintf('tag{%s}', $value), $settings);
            $parser->parse();
        } catch (\Throwable $e) {
            throw Exception::INVALID_CSS_STYLE($value);
        }
    }

    /**
     * Check URI format
     *
     * @param string $uri
     */
    private function checkUriString(string $uri)
    {
        if (!filter_var($uri, \FILTER_VALIDATE_URL)) {
            throw Exception::INVALID_URI($uri);
        }
    }

    /**
     * Check URI format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatUri(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }

        $this->checkUriString($document->getValue());
    }

    /**
     * Check uri-reference format
     *
     * @param ValueHelper $document
     * @param Schema $schema
     */
    private function formatUriReference(ValueHelper $document, Schema $schema)
    {
        // only applicable to strings
        if (!$document->isString()) {
            return;
        }
        $value = $document->getValue();

        try {
            // check as full URI first
            $this->checkUriString($value);
        } catch (\Throwable $e) {
            try {
                // check as reference
                if (substr($value, 0, 2) === '//') {
                    $this->checkUriString("scheme:$value"); // network-path reference
                } elseif (substr($value, 0, 1) === '/') {
                    $this->checkUriString("scheme://authority$value"); // absolute-path reference
                } elseif (substr($value, 0, 1) === '#') {
                    $this->checkUriString("scheme://authority/path$value"); // fragment-only reference
                } elseif (strlen($value)) {
                    $parts = explode('/', $value, 2);
                    if (strpos($parts[0], ':') !== false) {
                        throw ValidationException::INVALID_URI_REF($value);
                    }
                    $this->checkUriString("scheme://host/$value");
                } else {
                    throw Exception::INVALID_URI_REF($value);
                }
            } catch (\Throwable $e) {
                throw Exception::INVALID_URI_REF($value);
            }
        }
    }
}
