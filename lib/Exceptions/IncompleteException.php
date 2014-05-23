<?php namespace Matura\Exceptions;

class IncompleteException extends Exception
{
    public function getCategory()
    {
        return 'Incomplete';
    }
}
