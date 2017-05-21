<?php

namespace JsonValidator\Internal;

/**
 * Base instance class
 *
 * @package erayd/json-validator
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
abstract class BaseInstance
{
    /** @var global state */
    protected $state = null;

    /**
     * Create a new instance
     *
     * @param State $state
     */
    public function __construct(State $state)
    {
        $this->state = $state;
    }
}
