<?php namespace Matura\Blocks\Methods;

use Matura\Blocks\Block;

abstract class Method extends Block
{
    /**
     * Allows Method Block to act as callables. It somewhat nice ,a
     */
    public function __invoke($arguments)
    {
        return $this->invoke();
    }
}
