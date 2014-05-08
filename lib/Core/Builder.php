<?php namespace Matura\Core;

use Matura\Exceptions\Exception;
use Matura\Exceptions\SkippedException;

use Matura\Blocks\Methods\ExpectMethod;
use Matura\Blocks\Suite;
use Matura\Blocks\Describe;
use Matura\Blocks\Block;

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
 */
class Builder
{
    // Instance Properties
    // ###################

    /** @var array $method_map A simple mechanism to assist in method delegation. */
    private static $method_map = array(
        'suite'       => 'suite',
        'xsuite'      => 'suite',

        'describe'    => 'describe',
        'xdescribe'   => 'describe',

        'xit'         => 'addTest',
        'it'          => 'addTest',

        'before'      => 'before',
        'xbefore'     => 'before',

        'after'       => 'after',
        'xafter'      => 'after',

        'onceBefore'  => 'onceBefore',
        'xonceBefore' => 'onceBefore',

        'onceAfter'   => 'onceAfter',
        'xonceBefore' => 'onceAfter',
    );

    // DSL Dispatch
    // ############
    //
    // The global functions defined in functions.inc.php delegate to
    // corresponding methods in the builder object.


    // DSL Dispatch: Delegated To $this->current_test
    // ##############################################

    /**
     * Begins a fluent expectation, currently using esperance. Invoked when the
     * test is run (as compared to constructed e.g. describe, before).
     */
    public static function expect($obj)
    {
        $expect_method = new ExpectMethod(InvocationContext::closestBlock(), function($ctx) use (&$obj) {
            return new \Esperance\Assertion($obj);
        });

        return InvocationContext::invoke($expect_method, $expect_method->closestSuite());
    }

    // DSL Dispatch: Not delegated required.
    // #####################################
    public static function skip($message = '')
    {
        throw new SkippedException($message);
    }

    /**
     * Creates a new Describe block. The closure is invoked immediately.
     */
    public static function describe($description, $description_closure)
    {
        $next = new Describe(
            InvocationContext::closestDescribe(),
            $description_closure,
            $description
        );

        InvocationContext::closestDescribe()->addDescribe($next);

        return InvocationContext::invoke($next, InvocationContext::closestSuite());
    }

    /**
     * Creates a new Describe block. The closure is invoked immediately.
     */
    public static function suite($name, $fn)
    {
        $suite = Suite::factory($fn, $name);
        InvocationContext::invoke($suite, $suite);
        return $suite;
    }

    /**
     * Declares a new TestMethod and adds it to the current Describe block.
     */
    public static function it()
    {
        return call_user_func_array(
            array(
                InvocationContext::closestDescribe(),
                'addTest'
            ),
            func_get_args()
        );
    }

    /**
     * Everything else, including methods skipped via the dsl (e.g. xit).
     */
    public static function __callStatic($dsl_method_name, $arguments)
    {
        list($name, $skip) = self::getNameAndSkipFlag($dsl_method_name);

        $result = call_user_func_array(
            array(InvocationContext::closestDescribe(), $name),
            $arguments
        );

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
    protected static function getNameAndSkipFlag($name)
    {
        if ($name[0] == 'x') {
            return array(self::$method_map[$name], true);
        } else {
            return array(self::$method_map[$name], false);
        }
    }
}
