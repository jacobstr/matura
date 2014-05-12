<?php namespace Matura\Runners;

use SplFileInfo;
use Matura\Matura;
use Matura\Blocks\Suite;
use Matura\Blocks\Describe;
use Matura\Blocks\Methods\TestMethod;

use Matura\Core\ResultSet;
use Matura\Core\Result;

use Matura\Events\Listener;
use Matura\Events\Emitter;
use Matura\Events\Event;

use ArrayIterator;
use RegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FileSystemIterator;

abstract class Runner implements Emitter
{
    protected $listeners = array();

    public function addListener(Listener $listener)
    {
        $this->listeners[] = $listener;
    }

    public function emit($name, $arguments = array())
    {
        $event = new Event($name, $arguments);
        foreach ($this->listeners as $listener) {
            call_user_func(array($listener, 'onMaturaEvent'), $event);
        }
    }

    /**
     * @return ResultSet
     */
    abstract public function run();
}
