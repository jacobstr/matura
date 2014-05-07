<?php namespace Matura\Core;

use Matura\Blocks\Describe;
use Matura\Blocks\Methods\TestMethod;

use Matura\Exceptions\SkippedException;

use Matura\Core\TestContext;
use Matura\Core\ResultSet;
use Matura\Core\Result;

use Matura\Events\Listener;
use Matura\Events\Emitter;

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

    public function run(
        Describe $root_block,
        TestContext $context,
        ResultSet $result_set
    ) {
        $tests = $root_block->collectTests();

        foreach ($tests as $test) {
            $result_set->addResult($this->runTest($test, $context, $result_set));
        }

        $this->emit('test_set.complete', array($result_set));

        return $result_set;
    }

    public function runTest(TestMethod $test, TestContext $context, ResultSet $result_set = null)
    {
        $current_method = null;
        $return_value = null;

        $this->emit('test.start', array($result_set, $test));

        try {
            $return_value = $test->traverseMethods(function ($hook_or_test) use (&$current_method, $context) {
                $current_method = $hook_or_test;
                $hook_or_test->invoke($context);
            });
            $status = Result::SUCCESS;
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
