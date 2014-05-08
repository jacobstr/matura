<?php namespace Matura\Core;

use Matura\Blocks\Block;

class InvocationContext
{
    public static $stack = array();

    public static $total_invocations = 0;

    public static function closestSuite()
    {
        return self::stackGrepClass('\Matura\Blocks\Suite');
    }

    public static function closestDescribe()
    {
        return self::stackGrepClass('\Matura\Blocks\Describe');
    }

    public static function closestTest()
    {
        return self::stackGrepClass('\Matura\Blocks\Methods\TestMethod');
    }

    public static function closestBlock()
    {
        return self::stackGrepClass('\Matura\Blocks\Block');
    }

    public static function stackGrepClass($name)
    {
        foreach (array_reverse(self::$stack) as $block) {
            if (is_a($block, $name)) {
                return $block;
            }
        }

        return null;
    }

    public static function invoke(Block $block)
    {
        self::$total_invocations++;
        $args = array_slice(func_get_args(), 1);
        self::$stack[] = $block;
        $result = call_user_func_array(array($block,'invoke'), $args);
        array_pop(self::$stack);

        return $result;
    }

    public static function push(Block $block)
    {
        self::$stack[] = $block;
    }

    public static function pop(Block $block)
    {
        array_pop(self::$stack);
    }

    public static function activeBlock()
    {
        return end(self::$stack) ?: null;
    }
}
