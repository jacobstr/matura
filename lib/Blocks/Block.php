<?php namespace Matura\Blocks;

use Matura\Exceptions\SkippedException;
use Matura\Core\TestContext;
use Matura\Core\InvocationContext;

abstract class Block
{
    /** @var Callable $fn The method we're wrapping with testing bacon. */
    protected $fn;

    /** @var array A stack used to track Block invocatons. */
    protected $invocation_context;

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

    // Test Context via Magic Properties
    // #################################

    /** @var Arbitrary properties are set here. */
    protected $context = array();

    public function __get($name)
    {
        return $this->context[$name];
    }

    public function __set($name, $value)
    {
        $this->context[$name] = $value;
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

    public final function invoke()
    {
        return $this->invokeWithin($this->fn, array($this->closestSuite()));
    }

    public function invokeWithin($fn)
    {
        $this->invocation_context->activate();

        $this->invocation_context->push($this);
        try {
            $result = call_user_func($fn, $this->closestSuite());
            $this->invocation_context->pop();
            return $result;
        } catch(\Exception $e) {
            $this->invocation_context->pop();
            throw $e;
        } // Finally, some day.
    }

    public function addAssertion()
    {
        $this->assertions++;
    }

    public function getAssertionCount()
    {
        return $this->assertions;
    }

    public function path($start = null, $end = null)
    {
        $ancestors = array_map(
            function ($ancestor) {
                return $ancestor->name();
            },
            $this->ancestors()
        );

        $ancestors = array_slice(array_reverse($ancestors), $start, $end);

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
        $this->children[] = $block;
    }

    public function children($of_type = null)
    {
        if($of_type == null) {
            return $this->children;
        }

        return array_filter($this->children, function ($child) use ($of_type) {
            return $child instanceof $of_type;
        });
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
