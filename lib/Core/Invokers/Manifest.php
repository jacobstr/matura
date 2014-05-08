<?php namespace \Matura\Invokers\Manifest;

class Manifest
{
    protected $invokers = array();

    public function register($name, $invoker)
    {
        $this->invokers[$name] = $invoker;
    }

    public function get($name)
    {
        return $this->invokers[$name];
    }
}
