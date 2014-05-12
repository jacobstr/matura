<?php namespace Matura\Runners;

use Matura\Matura;
use Matura\Blocks\Block;
use Matura\Blocks\Methods\TestMethod;
use Matura\Blocks\Suite;

use Matura\Core\ResultSet;
use Matura\Core\Result;

class SuiteRunner extends Runner
{
    protected $suite;

    public function __construct(Suite $suite, ResultSet $result_set)
    {
        $this->suite = $suite;
        $this->result_set = $result_set;
    }

    public function run()
    {
        $this->emit('suite.start', array($this->suite, $this->result_set));
        $result = $this->runWithCapture(array($this, 'runGroup'), $this->suite);
        $this->emit('suite.end', array($this->suite, $this->result_set));
    }

    // Nested Blocks and Tests
    // #######################

    /**
     * @param $owner The Block 'owns' the result of $fn(). E.g. a TestMethod owns
     * the results from all of it's before and after Hooks.
     *
     * before_all and after_all hooks are owned by their encompassing Describe.
     */
    protected function runWithCapture($fn, Block $owner)
    {
        return $this->captureResult($fn, $owner);
    }

    protected function runGroup(Block $block)
    {
        $this->emit('group.start', array($block, $this->result_set));

        foreach($block->beforeAlls() as $before_all) {
            $before_all->invoke();
        }

        foreach($block->tests() as $test) {
            $this->emit('test.start', array($test, $this->result_set));

            $result = $this->runWithCapture(array($this, 'runTest'), $test);

            $this->result_set->addResult($result);

            $this->emit('test.complete', array($result, $this->result_set));
        }

        foreach($block->describes() as $describe) {
            $this->runGroup($describe, $this->result_set);
        }

        foreach($block->afterAlls() as $after_all) {
            $after_all->invoke();
        }

        $this->emit('group.complete', array($block, $this->result_set));
    }

    protected function runTest(TestMethod $test)
    {
        $test->traversePost(function($block) {
            foreach($block->befores() as $before) {
                $before->invoke();
            }
        });

        $result = $test->invoke();

        $test->traversePre(function($block) {
            foreach($block->afters() as $after) {
                $after->invoke();
            }
        });

        return $result;
    }

    public function captureResult($fn, Block $triggering_block)
    {
        try {
            // TODO $return_value not needed
            $return_value = $fn($triggering_block);
            $status = Result::SUCCESS;
        } catch (EsperanceError $e) {
            $status = Result::FAILURE;
            $return_value = new AssertionException($e->getMessage(), $e->getCode(), $e);
        } catch (SkippedException $e) {
            $status = Result::SKIPPED;
            $return_value = $e;
        } catch (Exception $e) {
            $status = Result::FAILURE;
            $return_value = new MaturaException($e->getMessage(), $e->getCode(), $e);
        }

        return new Result($triggering_block, $status, $return_value);
    }
}
