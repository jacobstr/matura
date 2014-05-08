<?php namespace Matura\Exceptions;

class SkippedException extends Exception
{
    public function getCategory()
    {
        return 'Skipped';
    }
}
