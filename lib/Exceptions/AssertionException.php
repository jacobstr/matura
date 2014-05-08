<?php namespace Matura\Exceptions;

/**
 * Wraps Assertion Failures. Third-party libraries should have their Exceptions
 * wrapped up in one of these via the TestRunner.
 */
class AssertionException extends Exception
{
    public function getCategory()
    {
        return 'Assertion Failure';
    }
}
