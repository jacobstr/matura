<?php namespace Matura\Core;

class ResultSet implements \IteratorAggregate
{
    private $results = array();

    public function __construct()
    {
    }

    public function addResult($result)
    {
        $this->results[] = $result;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->results);
    }

    public function totalAssertions()
    {
        $sum = 0;
        foreach ($this as $result) {
            $sum += $result->getAssertionCount();
        }
        return $sum;
    }

    public function totalFailures()
    {
        $sum = 0;
        foreach ($this as $result) {
            if ($result->isFailure()) {
                $sum++;
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

    public function totalTests()
    {
        return count($this->results);
    }

    public function totalSuccesses()
    {
        $sum = 0;
        foreach ($this as $result) {
            if ($result->isSuccess()) {
                $sum++;
            }
        }
        return $sum;
    }

    public function exitCode()
    {
        // Generate exit code based on result set.
        if ($this->totalFailures() === 0) {
            return 0;
        } else {
            return 1;
        }
    }
}
