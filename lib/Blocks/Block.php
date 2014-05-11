<?php namespace Matura\Blocks;

use Matura\Exceptions\SkippedException;
use Matura\Core\InvocationContext;
use Matura\Core\Context;

abstract class Block
{
    /** @var Callable $fn The method we're wrapping with testing bacon. */
    protected $fn;

    /** @var InvocationContext tracks our Block invocations in order to support
        * our dsl. */
    protected $invocation_context;

    /** @var Context Contains additional test-specific state. */
    protected $context;

    /**
     * @var string $name The block name. Used in output to identify tests and
     * for filtering by strings.
     */
    protected $name;

    /** @var Block $parent_block The block within which this method was defined.*/
    protected $parent_block;

    /** @var bool $skipped Whether this block was skipped during execution. */
    protected $skipped;

    /** @var bool $skipped An array of reasons for skipping a block. */
    protected $skipped_because = array();

    /** @var bool $invoked Whether this method has been invoked. */
    protected $invoked = false;

    /** @var int $assertions The number of assertions within this block's immediate $fn. */
    protected $assertions = 0;

    /** @var Block[] Child block elements. */
    protected $children = array();

    public function __construct(InvocationContext $invocation_context, $fn = null, $name = null)
    {
        $this->invocation_context = $invocation_context;
        $this->parent_block = $invocation_context->activeBlock();
        $this->fn = $fn;
        $this->name = $name;
    }

    /**
     * Unless the Block has been skipped elsewhere, this marks the block as
     * skipped with the given message.
     *
     * @param string $message An optional skip message.
     *
     * @return Block $this
     */
    public function skip($message = '')
    {
        if ($this->skipped !== true) {
            $this->skipped = true;
            $this->skipped_because = $message;
        }

        return $this;
    }

    public function isSkipped()
    {
        return $this->skipped;
    }

    // Test Context Management
    // #######################

    public function createContext()
    {
        return $this->context = new Context($this);
    }

    public function getContext()
    {
        return $this->context;
    }

    /**
     * Returns an aray of related contexts, in their intended call order.
     *
     * @see test_model.php for assertions against various scenarios in order to
     * grok the `official` behavior.
     *
     * @return Context[]
     */
    public function getContextChain()
    {
        $block_chain = array();

        // This should return all of our before hooks in the order they *should*
        // have been invoked.
        $this->traversePost(function($block) use (&$block_chain) {
            // Ensure ordering - even if the test defininition interleaves
            // onceBefore with before DSL invocations, we traverse the context
            // according to the 'onceBefores before befores' convention.
            $befores = array_merge($block->onceBefores(), $block->befores());
            $block_chain = array_merge($block_chain, $befores);
        });

        return array_filter(
            array_map(
                function($block) {
                    return $block->getContext();
                },
                $block_chain
            )
        );
    }

    // Invocation Context Management
    // #############################

    /**
     * Default external invocation method - calls the block originally passed into
     * the constructor along with a new context.
     */
    public function invoke()
    {
        return $this->invokeWithin($this->fn, array($this->createContext()));
    }

    /**
     * Invokes $fn with $args while managing our internal invocation context
     * in order to ensure our view of the test DSL's call graph is accurate.
     */
    protected function invokeWithin($fn, $args = array())
    {
        $this->invocation_context->activate();

        $this->invocation_context->push($this);
        try {
            $result = call_user_func_array($fn, $args);
            $this->invocation_context->pop();
            return $result;
        } catch(\Exception $e) {
            $this->invocation_context->pop();
            throw $e;
        } // Finally
    }

    public function addAssertion()
    {
        $this->assertions++;
    }

    public function getAssertionCount()
    {
        return $this->assertions;
    }

    /**
     * With no arguments, returns the complete path to this block down from it's
     * root ancestor.
     *
     * @param int $offset Used to arary_slice the intermediate array before implosion.
     * @param int $length Used to array_slice the intermediate array before implosion.
     */
    public function path($offset = null, $length = null)
    {
        $ancestors = array_map(
            function ($ancestor) {
                return $ancestor->name();
            },
            $this->ancestors()
        );

        $ancestors = array_slice(array_reverse($ancestors), $offset, $length);

        $res = implode(":", $ancestors);

        return $res;
    }

    public function name()
    {
        return $this->name;
    }

    // Traversal
    // #########

    public function ancestors()
    {
        $ancestors = array();
        $block = $this;

        while ($block) {
            $ancestors[] = $block;
            $block = $block->parentBlock();
        }

        return $ancestors;
    }

    public function parentBlock($parent_block = null)
    {
        if (func_num_args()) {
            $this->parent_block = $parent_block;
            return $this;
        } else {
            return $this->parent_block;
        }
    }

    public function addToParent()
    {
        if ($this->parent_block) {
            $this->parent_block->addChild($this);
        }
    }

    public function traversePost($fn)
    {
        if ($parent_block = $this->parentBlock()) {
            $parent_block->traversePost($fn);
        }

        $fn($this);
    }

    public function traversePre($fn)
    {
        $fn($this);

        if ($parent_block = $this->parentBlock()) {
            $parent_block->traversePre($fn);
        }
    }

    public function closest($class_name) {
        foreach ($this->ancestors() as $ancestor) {
            if (is_a($ancestor, $class_name)) {
                return $ancestor;
            }
        }
        return null;
    }

    // Traversing Upwards
    // ##################

    public function closestTest()
    {
        return $this->closest('Matura\Blocks\Methods\TestMethod');
    }

    public function closestSuite()
    {
        return $this->closest('Matura\Blocks\Suite');
    }

    // Retrieving and Filtering Child Blocks
    // #####################################

    public function addChild(Block $block)
    {
        $type = get_class($block);
        if(!isset($this->children[$type])) {
            $this->children[$type] = array();
        }
        $this->children[$type][] = $block;
    }

    public function children($of_type)
    {
        if(!isset($this->children[$of_type])) {
            $this->children[$of_type] = array();
        }
        return $this->children[$of_type];
    }

    public function tests()
    {
        return $this->children('Matura\Blocks\Methods\TestMethod');
    }

    /**
     * @var Block[] This Method's nested blocks.
     */
    public function describes()
    {
        return $this->children('Matura\Blocks\Describe');
    }

    /**
     * @return HookMethod[] All of our current `after` hooks.
     */
    public function afters()
    {
        return $this->children('Matura\Blocks\Methods\AfterHook');
    }

    /**
     * @return HookMethod[] All of our current `before` hooks.
     */
    public function befores()
    {
        return $this->children('Matura\Blocks\Methods\BeforeHook');
    }

    /**
     * @return HookMethod[] All of our current `onceBefore` hooks.
     */
    public function onceBefores()
    {
        return $this->children('Matura\Blocks\Methods\OnceBeforeHook');
    }

    /**
     * @return HookMethod[] All of our current `onceAfter` hooks.
     */
    public function onceAfters()
    {
        return $this->children('Matura\Blocks\Methods\OnceAfterHook');
    }
}
