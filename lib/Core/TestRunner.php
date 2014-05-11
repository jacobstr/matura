<?php namespace Matura\Core;

use SplFileInfo;
use Matura\Matura;
use Matura\Blocks\Suite;
use Matura\Blocks\Describe;
use Matura\Blocks\Methods\TestMethod;

use Matura\Exceptions\Exception;
use Matura\Exceptions\SkippedException;
use Matura\Exceptions\AssertionException;

use Matura\Core\ResultSet;
use Matura\Core\Result;

use Matura\Events\Listener;
use Matura\Events\Emitter;

use Esperance\Error as EsperanceError;
/**
 * Responsible for invoking files, Suites, and TestMethods.
 *
 * The test envirojnment is set up mostly in #run() where we register our
 * error handler and load our DSL.
 */
class TestRunner implements Emitter
{
    protected $listeners = array();

    protected $options = array(
        'filter' => '//',
        'grep' => '//'
    );

    /** @var The directory or folder contain our test file(s). */
    protected $path;

    public function __construct($path, $options = array()) {
        $this->path = $path;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Recursively obtains all test files under `$this->path` and returns
     * the filtered result after applying our filtering regex.
     *
     * @return \Iterator
     */
    public function collectFiles()
    {
        if (is_dir($this->path)) {
            $directory = new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS );
            $iterator = new \RecursiveIteratorIterator($directory);
            return new \RegexIterator($iterator, $this->options['filter']);
        } else {
            return new \ArrayIterator(array(new SplFileInfo($this->path)));
        }
    }

    public function addListener(Listener $listener)
    {
        $this->listeners[] = $listener;
    }

    public function emit($name, $arguments = array())
    {
        foreach ($this->listeners as $listener) {
            call_user_func(array($listener, 'onMaturaEvent'), $name, $arguments);
        }
    }

    /**
     * Bootstraps parts of our test enviornment and iteratively invokes each
     * file.
     *
     * @return ResultSet
     */
    public function run()
    {
        // With 5.4 and the ability to bind a closure's context, this will be
        // better off using a closure.
        Matura::init();

        $result_set = new ResultSet();

        $tests = $this->collectFiles();

        foreach($tests as $test) {
            $suite = new Suite(
                new InvocationContext(),
                function () use ($test) {
                    require $test;
                },
                $test->getRealPath()
            );
            $suite->build();
            $this->runSuite($suite, $result_set);
        }

        $this->emit('test_run.complete', array($result_set));

        Matura::cleanup();

        return $result_set;
    }

    /**
     * Runs a test suite.
     */
    protected function runSuite(
        Suite $suite,
        ResultSet $result_set
    ) {

        $this->emit('test_suite.start', array($suite, $result_set));

        $tests = $suite->collectTests($this->options['grep']);

        foreach ($tests as $test) {
            $result_set->addResult($this->runTest($suite, $test, $result_set));
        }

        $this->emit('test_suite.complete', array($suite, $result_set));

        return $result_set;
    }

    /**
     * Runs an individual test.
     */
    protected function runTest(Suite $suite, TestMethod $test, ResultSet $result_set = null)
    {
        $return_value = null;

        $this->emit('test.start', array($result_set, $test));

        try {
            $return_value = $test->traverseMethods(function ($hook_or_test) use ($suite) {
                $hook_or_test->invoke();
            });
            $status = Result::SUCCESS;
        } catch (EsperanceError $e) {
            $status = Result::FAILURE;
            $return_value = new AssertionException($e->getMessage(), $e->getCode(), $e);
        } catch (SkippedException $e) {
            $status = Result::SKIPPED;
            $return_value = $e;
        } catch (\Exception $e) {
            $status = Result::FAILURE;
            $return_value = new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $result = $this->buildResult($test, $status, $return_value);

        $this->emit('test.complete', array($result, $result_set, $test));

        return $result;
    }

    protected function buildResult($test, $status, $return_value)
    {
        return new Result($test, $status, $return_value);
    }
}
