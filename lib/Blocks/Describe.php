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

    protected $tests = array();
    protected $describes = array();

    protected $onceBefores = array();
    protected $befores = array();

    protected $onceAfters = array();
    protected $afters = array();

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

        foreach ($this->tests as $test) {
            if ($test->path() == $path) {
                return $test;
            }
        }

        foreach ($this->describes as $describe) {
            $found = $describe->find($path);
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

        foreach ($this->tests as $test) {
            $result[] = $test;
        }

        foreach ($this->describes as $describe) {
            $result = array_merge($result, $describe->collectTests());
        }

        return $result;
    }

    /**
     * @var string $name The name of the newly added TestMethod.
     * @var Callable $fn The callback body for the TestMethod.
     *
     * @return TestMethod The resulting TestMethod.
     */
    public function addTest($name, $fn)
    {
        $this->tests[] = $test = new TestMethod($this, $fn, $name);

        return $test;
    }

    /**
     * @var Describe $describe The nested Describe.
     *
     * @return null
     */
    public function addDescribe(Describe $describe)
    {
        $this->describes[] = $describe;
        return null;
    }

    /**
     * @var Block[] This Method's nested blocks.
     */
    public function describes()
    {
        return $this->describes;
    }

    // Nested Hooks
    // ############
    //
    // These are a speciality of the Describe blocks currently. They could very
    // well exist in the Block superclass but I'd rather be conservative with
    // that until use cases demand.

    /**
     * Adds a new after hook. Generally, to be run after every descendant
     * TestMethod.
     *
     * @param $fn A Callable that will be wrapped up as a new HookMethod.
     *
     * @return HookMethod The HookMethod created.
     */
    public function after($fn)
    {
        $this->afters[] = $result = new HookMethod($this, $fn, 'after');
        return $result;
    }

    /**
     * @return HookMethod[] All of our current `after` hooks.
     */
    public function afters()
    {
        return $this->afters;
    }

    /**
     * Adds a new `before` hook. Generally, to be run before every descendant
     * TestMethod.
     *
     * @param $fn A Callable that will be wrapped up as a new HookMethod.
     *
     * @return HookMethod The HookMethod created.
     */
    public function before($fn)
    {
        $this->befores[] = $result = new HookMethod($this, $fn, 'before');
        return $result;
    }

    /**
     * @return HookMethod[] All of our current `before` hooks.
     */
    public function befores()
    {
        return $this->befores;
    }

    /**
     * Adds a new `onceBefore` hook. Generally, to be run once when a descendant
     * TestMethod is invoked.
     *
     * @param $fn A Callable that will be wrapped up as a new HookMethod.
     *
     * @return HookMethod The HookMethod created.
     */
    public function onceBefore($fn)
    {
        $this->onceBefores[] = $result = new HookMethod($this, $fn, 'onceBefore');

        return $result;
    }

    /**
     * @return HookMethod[] All of our current `onceBefore` hooks.
     */
    public function onceBefores()
    {
        return $this->onceBefores;
    }

    /**
     * Adds a new `onceAfter` hook. Generally, to be run once when any descendant
     * TestMethod is invoked.
     *
     * @param $fn A Callable that will be wrapped up as a new HookMethod.
     *
     * @return HookMethod The HookMethod created.
     */
    public function onceAfter($fn)
    {
        $this->onceAfters[] = new HookMethod($this, $fn, 'onceAfter');
        return $this;
    }

    /**
     * @return HookMethod[] All of our current `onceAfter` hooks.
     */
    public function onceAfters()
    {
        return $this->onceAfters;
    }
}
