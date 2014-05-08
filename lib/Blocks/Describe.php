<?php namespace Matura\Blocks;

use Matura\Blocks\Methods\TestMethod;
use Matura\Blocks\Methods\HookMethod;

/**
 * A specialized Block for modelling a test suite.
 *
 * It maintains an awareness of child TestMethods, HookMethods and Describes
 * and allows to traverse them in a top-down manner.
 */
class Describe extends Block
{

    protected $children = array(
        'Matura\Blocks\Describe' => array(),
        'Matura\Blocks\Methods\TestMethod' => array(),
        'Matura\Blocks\Methods\AfterHook' => array(),
        'Matura\Blocks\Methods\BeforeHook' => array(),
        'Matura\Blocks\Methods\OnceAfterHook' => array(),
        'Matura\Blocks\Methods\OnceBeforeHook' => array()
    );

    /**
     * Finds a single TestMethod or Block with a given Path. We will return
     * the first match obtained - even though additional, ambiguous matches may
     * exist (name and path uniqueness is not enforced).
     *
     * @see Block#path
     *
     * @return Block
     */
    public function find($path)
    {
        if ($this->path() == $path) {
            return $this;
        }

        foreach ($this->tests() as $test) {
            if ($test->path() == $path) {
                return $test;
            }
        }
        foreach ($this->describes() as $block) {
            $found = $block->find($path);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    // Nested Blocks and Tests
    // #######################

    /**
     * Traverses the Describe graph recursively and all descendant TestMethods.
     *
     * Filtering is not done at this level.
     *
     * @return TestMethod[]
     */
    public function collectTests()
    {
        $result = array();

        foreach ($this->tests() as $test) {
            $result[] = $test;
        }

        foreach ($this->describes() as $describe) {
            $result = array_merge($result, $describe->collectTests());
        }

        return $result;
    }

    public function addChild(Block $block)
    {
        $block_class = get_class($block);
        if (!isset($this->children[$block_class])) {
            $this->children[$block_class] = array();
        }
        $this->children[$block_class][] = $block;
    }

    public function children()
    {
        $result = array();
        foreach ($this->children as $category) {
            $result = array_merge($result, $category);
        }
        return $result;
    }

    public function tests()
    {
        return $this->children['Matura\Blocks\Methods\TestMethod'];
    }

    /**
     * @var Block[] This Method's nested blocks.
     */
    public function describes()
    {
        return $this->children['Matura\Blocks\Describe'];
    }

    /**
     * @return HookMethod[] All of our current `after` hooks.
     */
    public function afters()
    {
        return $this->children['Matura\Blocks\Methods\AfterHook'];
    }

    /**
     * @return HookMethod[] All of our current `before` hooks.
     */
    public function befores()
    {
        return $this->children['Matura\Blocks\Methods\BeforeHook'];
    }

    /**
     * @return HookMethod[] All of our current `onceBefore` hooks.
     */
    public function onceBefores()
    {
        return $this->children['Matura\Blocks\Methods\OnceBeforeHook'];
    }

    /**
     * @return HookMethod[] All of our current `onceAfter` hooks.
     */
    public function onceAfters()
    {
        return $this->children['Matura\Blocks\Methods\OnceAfterHook'];
    }
}
