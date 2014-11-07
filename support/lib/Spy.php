<?php namespace Matura\Test;

class Spy
{
    public $invocations = array();

    public function __construct()
    {
    }

    public function __call($name, $args)
    {
        $this->invocations[] = $name;
    }
}
