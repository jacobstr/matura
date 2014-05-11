<?php namespace Matura\Core;

use Matura\Exceptions\Exception;
use Matura\Exceptions\SkippedException;

use Matura\Core\InvocationContext;

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
 * and actually executes the methods defined in the global DSL in function.php.
 */
class Builder
{
    // DSL Dispatch
    // ############
    //
    // The global functions defined in functions.inc.php delegate to
    // corresponding methods in the builder object. The syntactic sugar leans
    // on some clever tricks driven by a the interaction of the Builder and the 
    // InvocationContext.
    //
    //

    /**
     * Begins a fluent expectation using esperance. Invoked when the test is run
     * (as compared to constructed e.g. describe, before).
     */
    public static function expect($obj)
    {
        $expect_method = new ExpectMethod(InvocationContext::getActive(), function ($ctx) use (&$obj) {
            // Should, perhaps be configurable.
            return new \Esperance\Assertion($obj);
        });
        $expect_method->closestTest()->addAssertion();
        return $expect_method->invoke();
    }

    /**
     * Marks the test skipped and throws a SkippedException.
     */
    public static function skip($message = '')
    {
        $this->skipped = true;
        throw new SkippedException($message);
    }

    public static function describe($name, $fn)
    {
        $next = new Describe(InvocationContext::getActive(), $fn, $name);
        $next->addToParent();
        return $next;
    }

    public static function suite($name, $fn)
    {
        $suite = new Suite(new InvocationContext(), $fn, $name);
        $suite->build();
        return $suite;
    }

    public static function it($name, $fn)
    {
        $test_method = new TestMethod(InvocationContext::getActive(), $fn, $name);
        $test_method->addToParent();
        return $test_method;
    }

    public static function before($fn)
    {
        $test_method = new BeforeHook(InvocationContext::getActive(), $fn);
        $test_method->addToParent();
        return $test_method;
    }

    public static function before_all($fn)
    {
        $test_method = new OnceBeforeHook(InvocationContext::getActive(), $fn);
        $test_method->addToParent();
        return $test_method;
    }

    public static function after($fn)
    {
        $test_method = new AfterHook(InvocationContext::getActive(), $fn);
        $test_method->addToParent();
        return $test_method;
    }

    public static function after_all($fn)
    {
        $test_method = new OnceAfterHook(InvocationContext::getActive(), $fn);
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
            $result->skip();
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
     * >>$this->getNameAndSkipFlag('before_all');
     * array('before_all', false);
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
