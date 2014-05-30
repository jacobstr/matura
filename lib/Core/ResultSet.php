<?php namespace Matura\Core;

use IteratorAggregate;
use ArrayIterator;

class ResultSet implements ResultComponent, IteratorAggregate
{
    /**
     * @var ResultComponent[]
     */
    private $results = array();

    /**
     * @var int An iteratively updated test count. Should be equivalent to
     * totalTests().
     */
    private $total_tests;

    public function addResult($result)
    {
        $this->results[] = $result;
        $this->total_tests += $result->totalTests();
    }

    public function getIterator()
    {
        return new ArrayIterator($this->results);
    }

    public function totalAssertions()
    {
        $sum = 0;
        foreach ($this as $result) {
            $sum += $result->totalAssertions();
        }
        return $sum;
    }

    public function totalFailures()
    {
        $sum = 0;
        foreach ($this as $result) {
            $sum += $result->totalFailures();
        }
        return $sum;
    }

    public function totalSkipped()
    {
        $sum = 0;
        foreach ($this as $result) {
            $sum += $result->totalSkipped();
        }
        return $sum;
    }

    public function totalSuccesses()
    {
        $sum = 0;
        foreach ($this->results as $result) {
            $sum += $result->totalSuccesses();
        }
        return $sum;
    }

    public function totalTests()
    {
        $sum = 0;
        foreach ($this->results as $result) {
            $sum += $result->totalTests();
        }

        return $sum;
    }

    public function currentTestIndex()
    {
        return $this->total_tests;
    }

    public function isSuccessful()
    {
        // Generate exit code based on result set.
        if ($this->totalFailures() === 0) {
            return true;
        } else {
            return false;
        }
    }

    public function isFailure()
    {
        return ! $this->isSuccessful();
    }

    public function isSkipped()
    {
        // Need to think about this along with before all and after all failures
        // which are the most likely candidates for issues failures during
        // a result set invocation context.
        return false;
    }

    public function getFailures()
    {
        $failures = array();
        foreach ($this->results as $result) {
            $failures = array_merge($failures, $result->getFailures());
        }
        return $failures;
    }

    public function getExceptions()
    {
        $exceptions = array();
        foreach ($this->results as $result) {
            $exceptions = array_merge($exceptions, $result->getExceptions());
        }
        return $exceptions;
    }

    public function getStatus()
    {
        if($this->isFailure()) {
            return Result::FAILURE;
        } else if($this->isSkipped()) {
            return Result::SKIPPED;
        } else if($this->isSuccessful()) { // isSuccess seems more correct.
            return Result::SUCCESS;
        } else {
            return Result::INCOMPLETE;
        }
    }

    public function getStatusString()
    {
        switch($this->getStatus()) {
            case Result::SUCCESS:
                return 'success';
            case Result::FAILURE:
                return 'failure';
            case Result::SKIPPED:
                return 'skipped';
            case Result::INCOMPLETE:
                return 'incomplete';
            default:
                return null;
        }
    }
}
