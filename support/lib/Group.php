<?php namespace Matura\Test;

class Group
{
    // For testing static backup and restore.
    public static $groups = array();

    public $name;

    public function __construct($name)
    {
        $this->name = $name;
        static::$groups[] = $this;
    }
}
