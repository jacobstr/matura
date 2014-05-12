<?php namespace Matura\Blocks;

use Matura\Blocks\Methods\TestMethod;
use Matura\Blocks\Methods\HookMethod;

use Matura\Core\Result;

use Matura\Events\Emitter;
use Matura\Events\Listener;

use Exception;
use Matura\Exceptions\Exception as MaturaException;

/**
 * A specialized Block for modelling a test suite.
 *
 * It maintains an awareness of child TestMethods, HookMethods and Describes
 * and allows to traverse them in a top-down manner.
 */
class Describe extends Block
{
    protected $listeners = array();

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

}
