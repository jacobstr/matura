<?php namespace Matura\Events;

interface Emitter
{
    public function emit($name, $arguments = array());
    public function addListener(Listener $listener);
}
