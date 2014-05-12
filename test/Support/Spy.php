<?php namespace Matura\Test\Support;

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
