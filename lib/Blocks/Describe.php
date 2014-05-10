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
}
