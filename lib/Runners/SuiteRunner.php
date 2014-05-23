<?php namespace Matura\Runners;

use Matura\Matura;
use Matura\Blocks\Block;
use Matura\Blocks\Describe;
use Matura\Blocks\Methods\TestMethod;
use Matura\Blocks\Suite;

use Matura\Core\ResultSet;
use Matura\Core\Result;

use Matura\Exceptions\IncompleteException;
use Matura\Exceptions\SkippedException;

use Matura\Exceptions\Exception as MaturaException;

/**
 * Responsible for running a Suite and it's nested Describes, TestMethods, Before\After
 * Hooks and so on.
 *
 * It's a fairly top-down approach - an alternative might be to have TestMethods
 * know how to run themselves. However, there's a lot of machinery around
 * executing a test such as:
 *
 * 1. Invoking the *_all hooks only once per Suite.
 * 2. Invoking before/after hooks for each method.
 * 3. Wrapping both the hooks and test method execution in our captureResult
 *    method.
 * 4. Printing results in a somewhat granular fashion (start / complete events).
 *
 * That would convolute the responsibilities of our blocks.
 */
class SuiteRunner extends Runner
{
    protected $options;
    protected $suite;

    public function __construct(Suite $suite, ResultSet $result_set, $options = array())
    {
        $this->suite = $suite;
        $this->result_set = $result_set;
        $this->options = array_merge(array(
            'grep' => '//',
            'except' => null,
        ), $options);
    }

    /**
     * Runs the Suite from start to finish.
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

        $result = $this->runWithCapture(array($this, 'runGroup'), $this->suite);

        $this->emit(
            'suite.complete',
            array(
                'suite' => $this->suite,
                'result' => $result,
                'result_set' => $this->result_set
            )
        );

        if ($result->isFailure()) {
            $this->result_set->addResult($result);
        }
    }

    // Nested Blocks and Tests
    // #######################

    protected function runDescribe(Describe $describe)
    {
        $this->emit(
            'describe.start',
            array(
                'describe' => $describe,
                'result_set' => $this->result_set
            )
        );

        $result = $this->runWithCapture(array($this, 'runGroup'), $describe);

        $this->emit(
            'describe.complete',
            array(
                'describe' => $describe,
                'result' => $result,
                'result_set' => $this->result_set
            )
        );

        if ($result->isFailure()) {
            $this->result_set->addResult($result);
        }
    }

    protected function runGroup(Block $block)
    {
        // Check if the block should run by grepping it and descendants.
        if ($this->isFiltered($block)) {
            return;
        }

        foreach ($block->beforeAlls() as $before_all) {
            $before_all->invoke();
        }

        foreach ($block->tests() as $test) {
            $this->runTest($test);
        }

        foreach ($block->describes() as $describe) {
            $this->runDescribe($describe);
        }

        foreach ($block->afterAlls() as $after_all) {
            $after_all->invoke();
        }
    }

    protected function runTest(TestMethod $test)
    {
        // Check grep filter.
        if ($this->isFiltered($test)) {
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

            $test_return_value = $test->invoke();

            $test->traversePre(function ($block) {
                foreach ($block->afters() as $after) {
                    $after->invoke();
                }
            });

            return $test_return_value;
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

    /**
     * @param $owner The Block 'owns' the result of $fn(). E.g. a TestMethod owns
     * the results from all of it's before and after hooks.
     *
     * (before_all and after_all hooks are owned by their encompassing Describe)
     *
     * @return Result
     */
    protected function runWithCapture($fn, Block $owner)
    {
        try {
            $return_value = call_user_func($fn, $owner);
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

        return new Result($owner, $status, $return_value);
    }

    /**
     * Checks if the block or any of it's descendants match our grep filter or
     * do not match our except filter.
     *
     * Descendants are checked in order to retain a test even it's parent block
     * path does not match.
     */
    protected function isFiltered(Block $block)
    {
        // Skip filtering on implicit Suite block.
        if ($block instanceof Suite) {
            return false;
        }

        $options = &$this->options;

        $isFiltered = function ($block) use (&$options) {
            $filtered = false;

            if ($options['grep']) {
                $filtered = $filtered || preg_match($options['grep'], $block->path(0)) === 0;
            }

            if ($options['except']) {
                $filtered = $filtered || preg_match($options['except'], $block->path(0)) === 1;
            }

            return $filtered;
        };

        if ($block instanceof TestMethod) {
            return $isFiltered($block);
        } else {
            foreach ($block->tests() as $test) {
                if ($isFiltered($test) === false) {
                    return false;
                }
            }

            foreach ($block->describes() as $describe) {
                if ($this->isFiltered($describe) === false) {
                    return false;
                }
            }

            return true;
        }
    }
}
