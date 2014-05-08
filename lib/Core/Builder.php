<?php namespace Matura\Core;

use Matura\Exceptions\Exception;
use Matura\Exceptions\SkippedException;

use Matura\Blocks\Suite;
use Matura\Blocks\Describe;
use Matura\Blocks\Block;

use Matura\Blocks\Methods\ExpectMethod;
use Matura\Blocks\Methods\TestMethod;
use Matura\Blocks\Methods\BeforeHook;
use Matura\Blocks\Methods\OnceBeforeHook;
use Matura\Blocks\Methods\AfterHook;
use Matura\Blocks\Methods\OnceAfterHook;

/**
 * Enables the callback based sugar api to work the way it does. It maintains
 * and actually executes the methods defined in the global DSL (@see
 * functions.php).
 *
 * There's a bit of discordance currently with our Blocks and the Builder object.
 * I'm tempted to rename the class to Suite, for example. Possil
 *
 * Responsibilities:
 *  - Maintains state (stack of describe blocks, current block) as it builds the
 *    object graph that represents a file's tests.
 *  - Potentially maps user-friendly DSL parameters to more pedantic class and
 *    function arguments used internally.
 *
 *
 * Some pains have been taken to maintain
 */
class Builder
{
    // DSL Dispatch
    // ############
    //
    // The global functions defined in functions.inc.php delegate to
    // corresponding methods in the builder object.

    /**
     * Begins a fluent expectation using esperance. Invoked when the test is run
     * (as compared to constructed e.g. describe, before).
     */
    public static function expect($obj)
    {
        $expect_method = new ExpectMethod(null, function ($ctx) use (&$obj) {
            return new \Esperance\Assertion($obj);
        });
        $expect_method->closestTest()->addAssertion();
        return $expect_method->invoke();
    }

    /**
     * Skips the test.
     */
    public static function skip($message = '')
    {
        throw new SkippedException($message);
    }

    /**
     * Creates a new Describe block. The closure is invoked immediately.
     */
    public static function describe($name, $fn)
    {
        $next = new Describe(null, $fn, $name);
        $next->addToParent();
        $next->invoke();
        return $next;
    }

    /**
     * Creates a new Describe block. The closure is invoked immediately.
     */
    public static function suite($name, $fn)
    {
        $suite = new Suite(null, $fn, $name);
        $suite->invoke();
        return $suite;
    }

    /**
     * Declares a new TestMethod and adds it to the current Describe block.
     */
    public static function it($name, $fn)
    {
        $test_method = new TestMethod(null, $fn, $name);
        $test_method->addToParent();
        return $test_method;
    }

    public static function before($fn)
    {
        $test_method = new BeforeHook(null, $fn);
        $test_method->addToParent();
        return $test_method;
    }

    public static function onceBefore($fn)
    {
        $test_method = new OnceBeforeHook(null, $fn);
        $test_method->addToParent();
        return $test_method;
    }

    public static function after($fn)
    {
        $test_method = new AfterHook(null, $fn);
        $test_method->addToParent();
        return $test_method;
    }

    public static function onceAfter($fn)
    {
        $test_method = new OnceAfterHook(null, $fn);
        $test_method->addToParent();
        return $test_method;
    }

    /**
     * Takes care of our 'x' flag to skip any of the above methods.
     */
    public static function __callStatic($name, $arguments)
    {
        list($name, $skip) = self::getNameAndSkipFlag($name);

        $result = call_user_func_array(array('static', $name), $arguments);

        if ($skip === true && $result instanceof Block) {
            $result->skip('Skipped because method was x-prefixed');
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
    protected static function getNameAndSkipFlag($name)
    {
        if ($name[0] == 'x') {
            return array(substr($name, 1), true);
        } else {
            return array(self::$method_map[$name], false);
        }
    }
}
