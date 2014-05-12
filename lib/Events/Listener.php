<?php namespace Matura\Events;

interface Listener
{
    public function onMaturaEvent(Event $event);
}
