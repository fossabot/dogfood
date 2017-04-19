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
            case 'ip-address':
            case 'ipv4':
            case 'ipv6':
            case 'host-name': // PHP method names are not case-sensitive, so no alias is needed
            case 'hostname':
            case 'regex':
                if ($schema->getMeta('schema')->getSpec()->format($definition)) {
                    $definition = preg_replace_callback(
                        '/-([^-]+)/',
                        function ($matches) {
                            return ucfirst($matches[0]);
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
        $this->formatIpv4($value);
    }

    /**
     * Check IPv4 format
     *
     * @param string $value
     */
    private function formatIpv4(string $value)
    {
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
        // remove trailing period
        if (substr($value, -1) == '.') {
            $value = substr($value, strlen($value) - 1);
        }

        // check total length
        if (strlen($value) > 253) {
            throw ValidationException::HOSTNAME_TOO_LONG($value);
        }

        $nodes = explode('.', $value);
        array_walk($nodes, function($node) use($value) {
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
     * Check regex format
     *
     * @param string $value
     */
    private function formatRegex(string $value)
    {
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
}
