<?php namespace Matura\Core;

use Matura\Blocks\Suite;
use Matura\Blocks\Describe;
use Matura\Blocks\Methods\TestMethod;

use Matura\Exceptions\SkippedException;
use Matura\Exceptions\AssertionException;

use Matura\Core\TestContext;
use Matura\Core\ResultSet;
use Matura\Core\Result;

use Matura\Events\Listener;
use Matura\Events\Emitter;

use Esperance\Error as EsperanceError;

class TestRunner implements Emitter
{
    protected $root_block = null;
    protected $context = null;
    protected $result_set = null;
    protected $listeners = array();

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

    public function runSuite(
        Suite $suite,
        ResultSet $result_set
    ) {

        $this->emit('test_suite.start', array($result_set));

        $tests = $suite->collectTests();

        foreach ($tests as $test) {
            $result_set->addResult($this->runTest($suite, $test, $result_set));
        }

        $this->emit('test_suite.complete', array($result_set));

        return $result_set;
    }

    public function runTest(Suite $suite, TestMethod $test, ResultSet $result_set = null)
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
            $return_value = $e;
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
