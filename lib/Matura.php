<?php namespace Matura;

use Matura\Core\Builder;
use Matura\Core\ErrorHandler;
use Matura\Core\TestContext;

use Matura\Exceptions\Exception;

class Matura
{
    /** @var Builder $builder The active builder object. */
    protected static $builder;

    /** @var Builder $builder The active builder object. */
    protected static $error_handler;

    /** @var string[] $method_names The method names we magically support in or
     *  DSL.
     */
    protected static $method_names = array(
        'it',
        'xit',

        'onceBefore',
        'xonceBefore',

        'before',
        'xbefore',

        'after',
        'xafter',

        'onceAfter',
        'xonceAfter',

        'describe',
        'xdescribe',

        'expect',
        'skip'
    );

    /**
     * Private constructor - this class should not be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Gets the active builder.
     *
     * @return Builder
     */
    public static function getBuilder()
    {
        return self::$builder;
    }

    /**
     * Initializes Matura:
     *
     * 1. Sets our error handler as PHP's error handler.
     * 2. Imports our DSL into the global namespace.
     * 3. Initializes a new active Builder instance.
     *
     * @param Callable $fn An optional callback that is invoked with the current
     *  TestContext.
     * @param TestContext $test_context An optional, injectable test context.
     *
     * @return Builder
     */
    public static function start($fn = null, TestContext $test_context = null)
    {
        if (self::$builder !== null) {
            throw new Exception('A builder already exists! Reset first if you want to discard it.');
        }

        if ($fn !== null && ! is_callable($fn)) {
            throw new Exception('The $fn parameter was provided, but was not callable.');
        }

        require_once(__DIR__ . '/functions.php');

        self::$builder = new Builder($test_context);

        if ($fn) {
            $fn(self::getBuilder()->context());
        }

        return self::getBuilder();
    }


    public static function reset()
    {
        self::$builder = null;
    }

    /**
     * Workaround for the lack of https://wiki.php.net/rfc/use_function in
     * PHP < 5.6.
     *
     * Generates our functions.php consisting of a set of methods, in the global
     * namespace by default, that mostly forward to a singleton Builder instance.
     *
     * ATM this is not designed to be used by Matura's users - even though the
     * variable namespace support might appeal or even be a requirement for some
     * users. Feedback is welcome.
     *
     * It is *meant* to be run before pushing a release.
     *
     * @return string The code that was dumped into functions.php.
     */
    public static function exportDSL($target_namespace = '', $target_filename = null, $method_prefix = '')
    {
        $generate_method = function ($name) use ($method_prefix) {

            $prefixed_name = $method_prefix.$name;

            return <<<EOD

      function $prefixed_name()
      {
          return call_user_func_array(
              array(
                  \Matura\Matura::getBuilder(),
                  '$name'
              ),
              func_get_args()
          );
      }

EOD;
        };

        $method_code = implode(array_map($generate_method, $method_names), "");

        $final_code = <<<EOD
<?php
// Auto-generated with Matura::exportDSL()
namespace $target_namespace {
    $method_code
}
EOD;
        $target_filename = $target_filename ?: __DIR__.'/functions.php';

        file_put_contents($target_filename, $final_code);

        return $final_code;
    }
}
