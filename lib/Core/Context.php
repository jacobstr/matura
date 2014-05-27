<?php namespace Matura\Core;

use Matura\Blocks\Block;
use IteratorAggregate;
use ArrayIterator;

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

        return null;
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
