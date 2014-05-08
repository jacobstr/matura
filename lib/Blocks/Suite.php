<?php namespace Matura\Blocks;

use Matura\Blocks\Methods\TestMethod;
use Matura\Blocks\Methods\HookMethod;

class Suite extends Describe
{
    public function __construct(Block $parent_block = null, $fn = null, $name = null, TestContext $context = null)
    {
        parent::__construct($parent_block, $fn, $name, $context);
        $this->context = $context ?: new TestContext();
    }
}
