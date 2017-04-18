<?php

namespace Dogfood\Internal;

/**
 * Base instantiated class
 *
 * @package erayd/dogfood
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 */
class BaseInstance
{
    /** @var State Internal state **/
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
