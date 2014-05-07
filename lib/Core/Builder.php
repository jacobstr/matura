<?php namespace Matura\Core;

use Matura\Exceptions\Exception;
use Matura\Exceptions\SkippedException;
use Matura\Blocks\Describe;
use Matura\Blocks\Block;

/**
 * Enables the callback based sugar api to work the way it does. It maintains
 * and actually executes the methods defined in the global DSL (@see
 * functions.php).
 *
 * Responsibilities:
 *  - Maintains state (stack of describe blocks, current block) as it builds the
 *    object graph that represents a file's tests.
 *  - Potentially maps user-friendly DSL parameters to more pedantic class and
 *    function arguments used internally.
 */
class Builder
{
    private $describe_root = null;
    private $describe_stack = array();
    private $current_test = null;
    private $current_method = null;
    private $context = null;

    protected static $method_map = array(
        'describe' => 'describe',
        'xdescribe' => 'describe',

        'xit' => 'addTest',
        'it' => 'addTest',

        'before' => 'before',
        'xbefore' => 'before',

        'after' => 'after',
        'xafter' => 'after',

        'onceBefore' => 'onceBefore',
        'xonceBefore' => 'onceBefore',

        'onceAfter' => 'onceAfter',
        'xonceBefore' => 'onceAfter',
    );


    public function __construct(TestContext $context = null)
    {
        $this->context = $context ?: new TestContext();
    }

    public function context()
    {
        return $this->context;
    }

    public function current()
    {
        if (count($this->describe_stack)) {
            return end($this->describe_stack);
        } else {
            return null;
        }
    }

    public function find($path)
    {
        if ($this->describe_root === null) {
            throw new Exception("Cannot find without a describe block set.");
        }

        return $this->describe_root->find($path);
    }

    // DSL Dispatch
    // ############
    //
    // The global functions defined in functions.inc.php delegate to
    // corresponding methods in the builder object.


    // DSL Dispatch: Delegated To $this->current_test
    // #######################################
    public function expect($obj)
    {
        return $this->current_test->addAssertion($obj);
    }

    // DSL Dispatch: No delegation required.
    // #####################################
    public function skip($message = '')
    {
        throw new SkippedException($message);
    }

    // DSL Dispatch: Delegated to $this->current()
    // #####################################

    /**
     * Creates a new Describe block. The closure is invoked immediately.
     */
    public function describe($description, $description_closure)
    {
        $next = new Describe(
            $this->current(),
            $description_closure,
            $description
        );

        if ($this->current()) {
            // We're in a nested describe block.
            $this->current()->addDescribe($next);
        } else {
            // We assume only one top-level describe per file.
            if ($this->describe_root !== null) {
                throw new Exception(
                    "Defining a second, top-level describe block."
                );
            }
            // We've just defined the top-level describe block.
            $this->describe_root = $next;
        }

        $this->describe_stack[] = $next;

        $description_closure($this->context);

        return array_pop($this->describe_stack);
    }

    /**
     * Declares a new TestMethod and adds it to the current Describe block.
     */
    public function it()
    {
        $this->current_test = call_user_func_array(
            array(
                $this->current(),
                'addTest'
            ),
            func_get_args()
        );
        return $this->current_test;
    }

    /**
     * Everything else, including methods skipped via the dsl (e.g. xit).
     */
    public function __call($dsl_name, $arguments)
    {
        list($name, $skip) = $this->getNameAndSkipFlag($dsl_name);

        $result = call_user_func_array(array($this->current(), $name), $arguments);

        if ($skip === true && $result instanceof Block) {
            $result->skip('Skipped because method was prefixed by `x`');
        }

        return $result;
    }

    // DSL Utility Methods
    // ###################

    /**
     * Used to detect skipped versions of methods.
     *
     * @example
     * >>$this->getNameAndSkipFlag('xit');
     * array('it', true);
     *
     * >>$this->getNameAndSkipFlag('onceBefore');
     * array('onceBefore', false);
     *
     * @return a 2-tuple of a method name and skip flag.
     */
    protected function getNameAndSkipFlag($name)
    {
        if ($name[0] == 'x') {
            return array(self::$method_map[$name], true);
        } else {
            return array(self::$method_map[$name], false);
        }
    }
}
