<?php namespace Matura\Blocks;

use Matura\Blocks\Methods\TestMethod;
use Matura\Blocks\Methods\HookMethod;
use Matura\Exceptions\Exception;

/**
 * Meant as a top-level container - e.g. 1 per file. This is not enforced, simply
 * discouraged.
 */
class Suite extends Describe
{
    public function build()
    {
        $builder_for = function($block)  use (&$builder_for) {
            return function ($suite) use ($block, $builder_for) {
                $block->invoke();
                foreach($block->describes() as $describe) {
                    $describe->invokeWithin($builder_for($describe));
                }
            };
        };

        return $this->invokeWithin($builder_for($this));
    }
}
