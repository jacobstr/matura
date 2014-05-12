<?php namespace Matura\Core;

use Matura\Blocks\Block;
use Matura\Blocks\Methods\TestMethod;

class Result
{
    const SUCCESS = 2;
    const SKIPPED = 1;
    const FAILURE = 0;

    /**
     * @var TestMethod $method The TestMethod which was run to obtain the given
     * result.
     */
    protected $method;

    /**
     * @var int $status The status code for a test.
     *
     * 0 - failed
     * 1 - skipped
     * 2 - success
     */
    protected $status = null;

    /** @var mixed $result The return value or Exception raised by a test. */
    protected $result = null;

    public function __construct(Block $method, $status, $returned)
    {
        $this->method   = $method;
        $this->status   = $status;
        $this->returned = $returned;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getReturned()
    {
        return $this->returned;
    }

    public function getException()
    {
        if ($this->returned instanceof \Exception) {
            return $this->returned;
        } else {
            return null;
        }
    }

    public function getAssertionCount()
    {
        return $this->method->getAssertionCount();
    }

    public function isSuccess()
    {
        return $this->status == static::SUCCESS;
    }

    public function isFailure()
    {
        return $this->status == static::FAILURE;
    }

    public function isSkipped()
    {
        return $this->status == static::SKIPPED;
    }
}
