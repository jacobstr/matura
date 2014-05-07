<?php namespace Matura\Events;

interface Listener
{
    public function onMaturaEvent($name, $args);
}
