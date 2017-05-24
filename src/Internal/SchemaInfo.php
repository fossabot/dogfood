<?php

namespace JsonValidator\Internal;

use JsonValidator\Exception;

/**
 * Helper for working with values
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class SchemaInfo extends \Erayd\JsonSchemaInfo\SchemaInfo
{
    /**
     * Get the correct ID keyword to use for this spec version
     *
     * @return string
     */
    public function getIdKeyword() : string
    {
        return $this->core->{'$id'} ? '$id' : 'id';
    }
}
