<?php namespace Matura\Blocks;

use Matura\Exceptions\SkippedException;
use Matura\Core\TestContext;
use Matura\Core\InvocationContext;

abstract class Block
{
    /** @var Callable $fn The method we're wrapping with testing bacon. */
    protected $fn;

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

    public function __construct(Block $parent_block = null, $fn = null, $name = null)
    {
        $this->parent_block = $parent_block;
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

    public function invoke(Suite $suite)
    {
        if ($this->isSkipped()) {
            throw new SkippedException($this->skipped_because);
        }

        $this->invoked = true;

        return call_user_func($this->fn, $suite);
    }

    public function addAssertion($obj)
    {
        $this->assertions++;
    }

    public function getAssertionCount()
    {
        return $this->assertions;
    }

    public function path()
    {
        $ancestors = array_map(
            function ($ancestor) {
                return $ancestor->name();
            },
            $this->ancestors()
        );

        $ancestors = array_reverse($ancestors);

        $res = implode(":", $ancestors);

        return $res;
    }

    // Fluent Accessors
    // ################

    public function name($name = null)
    {
        if (func_num_args()) {
            $this->name = $name;
            return $this;
        } else {
            return $this->name;
        }
    }

    // Traversal
    // #########

    public function closestSuite()
    {
        foreach($this->ancestors() as $ancestor) {
            if(is_a($ancestor, '\Matura\Blocks\Suite')) {
                return $ancestor;
            }
        }
        return null;
    }

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

    // Invocation Stack

    protected static function closestDescribe()
    {
        return $this->stackGrepClass('\Matura\Blocks\Describe');
    }

    protected function closestTest()
    {
        return $this->stackGrepClass('\Matura\Blocks\Methods\TestMethod');
    }
}
