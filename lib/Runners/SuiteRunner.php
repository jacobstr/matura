<?php namespace Matura\Runners;

use Matura\Matura;
use Matura\Blocks\Block;
use Matura\Blocks\Methods\TestMethod;
use Matura\Blocks\Suite;

use Matura\Core\ResultSet;
use Matura\Core\Result;

use Matura\Exceptions\Exception as MaturaException;

class SuiteRunner extends Runner
{
    protected $options;
    protected $suite;

    public function __construct(Suite $suite, ResultSet $result_set, $options = array())
    {
        $this->suite = $suite;
        $this->result_set = $result_set;
        $this->options = array_merge(array(
            'grep' => '//'
        ), $options);
    }

    /**
     * Runs the Suite from start to finish.
     *
     * @return Result
     */
    public function run()
    {
        $this->emit(
            'suite.start',
            array(
                'suite' => $this->suite,
                'result_set' => $this->result_set
            )
        );
        // TODO this can swallow errors.
        $result = $this->runWithCapture(array($this, 'runGroup'), $this->suite);

        $this->emit(
            'suite.complete',
            array(
                'suite' => $this->suite,
                'result_set' => $this->result_set
            )
        );

        return $result;
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
        $result = $this->captureResult($fn, $owner);
        return $result;
    }

    protected function runGroup(Block $block)
    {
        // Check if the block should run by grepping it and descendants.
        if (!$this->grep($block)) {
            return;
        }

        foreach ($block->beforeAlls() as $before_all) {
            $before_all->invoke();
        }

        foreach ($block->tests() as $test) {
            $this->runTest($test);
        }

        foreach ($block->describes() as $describe) {
            $this->emit('describe.start', array('describe' => $describe, 'result_set' => $this->result_set));

            $this->runGroup($describe, $this->result_set);

            $this->emit('describe.complete', array('describe' => $describe, 'result_set' => $this->result_set));
        }

        foreach ($block->afterAlls() as $after_all) {
            $after_all->invoke();
        }
    }

    protected function runTest(TestMethod $test)
    {
        // Check grep filter.
        if (!$this->grep($test)) {
            return;
        }

        $start_context = array(
            'test' => $test,
            'result_set' => $this->result_set
        );

        $this->emit('test.start', $start_context);

        $result = $this->runWithCapture(function () use ($test) {
            $test->traversePost(function ($block) {
                foreach ($block->befores() as $before) {
                    $before->invoke();
                }
            });

            $result = $test->invoke();

            $test->traversePre(function ($block) {
                foreach ($block->afters() as $after) {
                    $after->invoke();
                }
            });
        }, $test);

        $this->result_set->addResult($result);

        $complete_context = array(
            'test' => $test,
            'result' => $result,
            'result_set' => $this->result_set
        );

        $this->emit('test.complete', $complete_context);

        return $result;
    }

    public function captureResult($fn, Block $triggering_block)
    {
        try {
            $return_value = call_user_func($fn, $triggering_block);
            $status = Result::SUCCESS;
        } catch (EsperanceError $e) {
            $status = Result::FAILURE;
            $return_value = new AssertionException($e->getMessage(), $e->getCode(), $e);
        } catch (SkippedException $e) {
            $status = Result::SKIPPED;
            $return_value = $e;
        } catch (\Exception $e) {
            $status = Result::FAILURE;
            $return_value = new MaturaException($e->getMessage(), $e->getCode(), $e);
        }

        return new Result($triggering_block, $status, $return_value);
    }

    public function grep(Block $block)
    {
        // Skip filtering on implicit Suite block.
        if ($block instanceof Suite) {
            return true;
        }

        $options = &$this->options;

        $match = function ($block) use (&$options) {
            return preg_match($options['grep'], $block->path(0)) === 1;
        };

        if ($block instanceof TestMethod) {
            return $match($block);
        }

        $recur = function(Block $block) use (&$recur, &$match) {
            foreach($block->tests() as $test) {
                if($match($test)) {
                    return true;
                }
            }

            foreach($block->describes() as $describe) {
                if ($recur($describe)) {
                    return true;
                }
            }

            return false;
        };

        return $recur($block);
    }
}
