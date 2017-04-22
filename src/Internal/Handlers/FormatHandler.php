<?php

namespace Dogfood\Internal\Handlers;

use Sabberworm\CSS\Parser as CSSParser;
use Sabberworm\CSS\Settings as CSSSettings;

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
        // the following pre-defined formats are deliberately not validated:
        //  - phone  (format specification is vague, and only says MAY follow E.123)
        //  - uriref (pending resolution of json-schema-spec issue #310)

        // validate pre-defined formats
        switch ($definition) {
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
                if ($schema->getMeta('schema')->getSpec()->format($definition)) {
                    $definition = preg_replace_callback(
                        '/-([^-]+)/',
                        function ($matches) {
                            return ucfirst($matches[1]);
                        },
                        $definition
                    );
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
     * Alias for formatIpv4
     *
     * @param string $value
     */
    private function formatIpAddress(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        $this->formatIpv4($value);
    }

    /**
     * Check IPv4 format
     *
     * @param string $value
     */
    private function formatIpv4(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        if (filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) === false) {
            throw ValidationException::INVALID_IPV4($value);
        }
    }

    /**
     * Check IPv6 format
     *
     * @param string $value
     */
    private function formatIpv6(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        if (filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) === false) {
            throw ValidationException::INVALID_IPV6($value);
        }
    }

    /**
     * Check hostname format
     *
     * @param string $value
     */
    private function formatHostname(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        // remove trailing period
        if (substr($value, -1) == '.') {
            $value = substr($value, strlen($value) - 1);
        }

        // check total length
        if (strlen($value) > 253) {
            throw ValidationException::HOSTNAME_TOO_LONG($value);
        }

        $nodes = explode('.', $value);
        array_walk($nodes, function ($node) use ($value) {
            // check node length
            if (strlen($node) > 63) {
                throw ValidationException::HOSTNAME_COMPONENT_TOO_LONG($value);
            }
            // check node format
            if (!preg_match('/^(?:[a-z](?:-?[a-z0-9]+)?)?$/i', $node)) {
                throw ValidationException::INVALID_HOSTNAME_COMPONENT($node);
            }
        });
    }

    /**
     * Check email format
     *
     * @param string $value
     */
    private function formatEmail(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        $unicode = defined('\FILTER_FLAG_EMAIL_UNICODE') ? constant('\FILTER_FLAG_EMAIL_UNICODE') : 0;
        if (filter_var($value, \FILTER_VALIDATE_EMAIL, $unicode) === false) {
            throw ValidationException::INVALID_EMAIL($value);
        }
    }

    /**
     * Check regex format
     *
     * @param string $value
     */
    private function formatRegex(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        $pattern = ValueHelper::patternToPCRE($value);

        // check that the expression compiles
        set_error_handler(function () use ($value) {
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

    /**
     * Check date-time format
     *
     * @param string $value
     */
    private function formatDateTime(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        // check date-time format
        $regex = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\.[0-9]+)?(?:Z|[+-][0-9]{2}:[0-9]{2})$/i';
        if (!preg_match($regex, $value, $matches)) {
            throw ValidationException::INVALID_DATETIME($value);
        }

        // check data is sane
        $format = 'Y-m-d\TH:i:s' . (isset($matches[1]) ? '.u' : '') . 'P';
        if (!\DateTime::createFromFormat($format, strtoupper($value))) {
            throw ValidationException::INVALID_DATETIME($value);
        }
    }

    /**
     * Check date format
     *
     * @param string $value
     */
    private function formatDate(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        // check date format
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)) {
            throw ValidationException::INVALID_DATE($value);
        }

        // check data is sane
        if (!\DateTime::createFromFormat('Y-m-d', $value)) {
            throw ValidationException::INVALID_DATE($value);
        }
    }

    /**
     * Check time format
     *
     * @param string $value
     */
    private function formatTime(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        // check date format
        if (!preg_match('/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $value)) {
            throw ValidationException::INVALID_TIME($value);
        }

        // check data is sane
        if (!\DateTime::createFromFormat('H:i:s', $value)) {
            throw ValidationException::INVALID_TIME($value);
        }
    }

    /**
     * Check CSS colour format
     *
     * @param string $value
     */
    private function formatColor(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        $colours = ['maroon', 'red', 'orange', 'yellow', 'olive', 'purple', 'fuchsia', 'white',
            'lime', 'green', 'navy', 'blue', 'aqua', 'teal', 'black', 'silver', 'gray'];

        if (preg_match('/^#[0-9a-f]{3}$/i', $value)) {
            // 3-digit hex | #369
        } elseif (preg_match('/^#[0-9a-f]{6}$/i', $value)) {
            // 6-digit hex | #336699
        } elseif (preg_match('/^rgb\(([0-9]+),([0-9]+),([0-9]+)\)$/', preg_replace('/\s+/', '', $value), $matches)) {
            // rgb-bracketed | rgb(51, 102, 153)
            if ($matches[1] > 255 || $matches[2] > 255 || $matches[3] > 255) {
                throw ValidationException::INVALID_CSS_COLOR($value);
            }
        } elseif (in_array(strtolower($value), $colours)) {
            // predefined css colour name
        } else {
            // no idea what this is, but it's not a valid colour
            throw ValidationException::INVALID_CSS_COLOR($value);
        }
    }

    /**
     * Check utc-millisec format
     *
     * @param string $value
     */
    private function formatUtcMillisec(string $value)
    {
        if (!is_numeric($value)) {
            throw ValidationException::INVALID_UTC_MILLISEC($value);
        }
    }

    /**
     * Check style format
     *
     * @param string $value
     */
    private function formatStyle(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        $settings = CSSSettings::create()->beStrict();
        try {
            $parser = new CSSParser(sprintf('tag{%s}', $value), $settings);
            $parser->parse();
        } catch (\Throwable $e) {
            throw ValidationException::INVALID_CSS_STYLE($value);
        }
    }

    /**
     * Check URI format
     *
     * @param string $value
     */
    private function formatUri(string $value)
    {
        // only applicable to strings
        if (!is_string($value)) {
            return;
        }

        if (null === filter_var($value, \FILTER_VALIDATE_URL, \FILTER_NULL_ON_FAILURE)) {
            // FILTER_VALIDATE_URL does not conform to RFC-3986, and cannot handle relative URLs, but
            // the json-schema spec uses RFC-3986, so need a bit of hackery to properly validate them.
            // See https://tools.ietf.org/html/rfc3986#section-4.2 for additional information.
            if (substr($value, 0, 2) === '//') { // network-path reference
                $validURL = filter_var('scheme:' . $value, \FILTER_VALIDATE_URL, \FILTER_NULL_ON_FAILURE);
            } elseif (substr($value, 0, 1) === '/') { // absolute-path reference
                $validURL = filter_var('scheme://host' . $value, \FILTER_VALIDATE_URL, \FILTER_NULL_ON_FAILURE);
            } elseif (substr($value, 0, 1) === '#') { // fragment-only reference
                $validURL = filter_var('scheme://host/path' . $value, \FILTER_VALIDATE_URL, \FILTER_NULL_ON_FAILURE);
            } elseif (strlen($value)) { // relative-path reference
                $pathParts = explode('/', $value, 2);
                if (strpos($pathParts[0], ':') !== false || substr($pathParts[0], 0, 2) == '\\\\') {
                    $validURL = null;
                } else {
                    $validURL = filter_var('scheme://host/' . $value, \FILTER_VALIDATE_URL, \FILTER_NULL_ON_FAILURE);
                }
            } else {
                $validURL = null;
            }
            if (is_null($validURL)) {
                throw ValidationException::INVALID_URI($value);
            }
        }
    }
}
