<?php namespace Matura\Runners;

use Matura\Events\Listener;
use Matura\Events\Emitter;
use Matura\Events\Event;

/**
 * Runners drive our test execution. The TestRunner runs individual test files,
 * the SuiteRunner runs the test Suite contained within.
 *
 * Both runners emit events and that is one of the main reasons this class exists
 * - in lieu of Traits as long as 5.3 is supported.
 *
 * @see TestRunner
 * @see SuiteRunner
 */
abstract class Runner implements Emitter
{
    protected $listeners = array();

    protected $result_set;

    public function addListener(Listener $listener)
    {
        $this->listeners[] = $listener;
    }

    public function emit($name, $arguments = array())
    {
        $event = new Event($name, $arguments);
        foreach ($this->listeners as $listener) {
            $this->invokeEventHandler($event, $listener);
        }
    }

    protected function invokeEventHandler(Event $event, Listener $listener)
    {
        $parts = array_map('ucfirst', array_filter(preg_split('/_|\./', $event->name)));
        $name = 'on'.implode($parts);

        if (is_callable(array($listener, $name))) {
            return call_user_func(array($listener, $name), $event);
        } else {
            return call_user_func(array($listener, 'onMaturaEvent'), $event);
        }
    }

    public function getResultSet()
    {
        return $this->result_set;
    }

    /**
     * @return ResultSet
     */
    abstract public function run();
}
