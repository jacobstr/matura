<?php namespace Matura\Blocks;

use Matura\Blocks\Methods\TestMethod;
use Matura\Blocks\Methods\HookMethod;

class Suite extends Describe
{
    /** @var Arbitrary properties are set here. */
    protected $context = array();

    protected static $suites = array();

    public function __construct()
    {
        call_user_func_array(array('parent','__construct'), func_get_args());
        self::registerSuite($this);
    }

    // Suite Selection and Activation
    // ################################

    public static function getSuite($suite_name)
    {
        return static::$suites[$suite_name];
    }

    public static function registerSuite(Suite $suite)
    {
        if (isset(static::$suites[$suite->name()])) {
            throw new Exception("Suite with name {$suite->name()} already exists");
        }

        static::$suites[$suite->name()] = $suite;
    }

    public static function getLastSuite()
    {
        return end(static::$suites);
    }

    // Test Context via Magic Properties
    // #################################

    public function __get($name)
    {
        return $this->context[$name];
    }

    public function __set($name, $value)
    {
        $this->context[$name] = $value;
    }
}
