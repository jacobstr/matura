<?php namespace Matura\Core;

use Matura\Blocks\Block;
use Matura\Core\InvocationContext;

class InvocationContext
{
    protected $stack = array();

    protected $total_invocations = 0;

    protected static $active_invocation_context;

    public function closestSuite()
    {
        return $this->closest('\Matura\Blocks\Suite');
    }

    public function closestDescribe()
    {
        return $this->closest('\Matura\Blocks\Describe');
    }

    public function closestTest()
    {
        return $this->closest('\Matura\Blocks\Methods\TestMethod');
    }

    public function closestBlock()
    {
        return $this->closest('\Matura\Blocks\Block');
    }

    public function closest($name)
    {
        foreach (array_reverse($this->stack) as $block) {
            if (is_a($block, $name)) {
                return $block;
            }
        }

        return null;
    }

    public function invoke(Block $block)
    {
        $this->total_invocations++;
        $args = array_slice(func_get_args(), 1);
        $this->stack[] = $block;
        $result = call_user_func_array(array($block,'invoke'), $args);
        array_pop($this->stack);

        return $result;
    }

    public function push(Block $block)
    {
        $this->stack[] = $block;
    }

    public function pop()
    {
        array_pop($this->stack);
    }

    public function activeBlock()
    {
        return end($this->stack) ?: null;
    }

    public function activate()
    {
        static::$active_invocation_context = $this;
    }

    public static function getActive()
    {
        return static::$active_invocation_context;
    }
}
