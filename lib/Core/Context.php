<?php namespace Matura\Core;

use Matura\Blocks\Block;
use IteratorAggregate;
use ArrayIterator;

/**
 * Manages the context variable that is provided to individual tests.
 *
 * The most magical thing this does is to allow tests to shadow / overwrite
 * variables defined in other Blocks without clobbering the previous values.
 *
 * @see test/functional/test_context.php Specifically, the Isolation tests where
 * we assert that a sibling describe block will not use the variables clobbered
 * by a sibling.
 *
 * There's a few advantages to this approach. First, it discourages writing tests
 * that depend on previous test results e.g. by having succesive test that
 * modify the context. Of course, if you've got an an object and you're modifying
 * it's internal state, we don't try to prevent that.
 *
 * Second, the behavior may be exploited. One of the ideas guiding Matura's
 * nesting strategy is you want to be able to move from general to specific,
 * augmenting the context along the way. Two blocks may share a common ancestor
 * that defines some base context and in turn, they can modify this context
 * without affecting each others behavior. You could do this simply through
 * judicious use of before() blocks to establish a 'fresh' context for each and
 * every test case, but sometimes you *do* want a shared context for the purpose
 * of efficiency.
 */
class Context implements IteratorAggregate
{

    /** @var Arbitrary properties. Exposed to the context passed into a test. */
    private $context = array();

    public $block;

    public function __construct(Block $block)
    {
        $this->block = $block;
    }

    public function __get($name)
    {
        if (isset($this->context[$name])) {
            return $this->context[$name];
        }

        foreach (array_reverse($this->block->getContextChain()) as $context) {
            if ($context->getImmediate($name) !== null) {
                // Cache the value.
                $this->context[$name] = $context->getImmediate($name);
                return $this->context[$name];
            }
        }

        return null;
    }

    public function __call($name, $arguments)
    {
        if (isset($this->context[$name])) {
            return call_user_func_array($this->context[$name], $arguments);
        }

        foreach (array_reverse($this->block->getContextChain()) as $context) {
            if ($context->getImmediate($name) !== null) {
                // Cache the value.
                $this->context[$name] = $context->getImmediate($name);
                return call_user_func_array($this->context[$name], $arguments);
            }
        }

        throw new \Exception("Method $name does not exist.");
    }

    public function getImmediate($key)
    {
        return array_key_exists($key, $this->context) ? $this->context[$key] :  null;
    }
    /**
     * Sets a value. Always on myself and never on member of the context chain.
     */
    public function __set($name, $value)
    {
        $this->context[$name] = $value;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->context);
    }
}
