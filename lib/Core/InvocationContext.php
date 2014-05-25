<?php namespace Matura\Core;

use Matura\Blocks\Block;
use Matura\Core\InvocationContext;

/**
 * Tracks the call stack for our DSL related methods and provides some tools to
 * traverse them. When the external DSL method 'suite' is invoked, we create
 * a disjoint context stack. This is used primarily to created a test suite
 * within a test suite during the self-hosted tests.
 */
class InvocationContext
{
    /**
     * @var Block[] $stack
     * Call stack within a single InvocationContext - each
     */
    protected $stack = array();

    /**
     * @var InvocationContext[] $contexts
     * Stack of invocation contexts - this is generally a single item, except in
     * cases like `test/test_ordering.php` where we create a self-hosted test
     * suite.
     */
    protected static $contexts = array();

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
        static::$contexts[] = $this;
    }

    public function deactivate()
    {
        array_pop(static::$contexts);
        static::$active_invocation_context = end(static::$contexts);
    }

    public static function getActive()
    {
        return static::$active_invocation_context;
    }
}
