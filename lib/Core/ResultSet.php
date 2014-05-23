<?php namespace Matura\Core;

use IteratorAggregate;
use ArrayIterator;

class ResultSet implements ResultComponent, IteratorAggregate
{
    private $results = array();

    public function addResult($result)
    {
        $this->results[] = $result;
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
            if ($result->isFailure()) {
                $sum += $result->totalFailures();
            }
        }
        return $sum;
    }

    public function totalSkipped()
    {
        $sum = 0;
        foreach ($this as $result) {
            if ($result->isSkipped()) {
                $sum++;
            }
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
}
