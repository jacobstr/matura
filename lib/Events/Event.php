<?php namespace Matura\Events;

class Event
{
    protected $context = array();

    public function __construct($name, $context = array())
    {
        $this->name = $name;
        $this->context = array_merge($this->context, $context);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function __get($name)
    {
        return $this->context[$name];
    }
}
