<?php namespace Matura\Console\Output;

use Matura\Core\Result;
use Matura\Events\Event;

use Matura\Blocks\Block;
use Matura\Blocks\Suite;
use Matura\Blocks\Describe;
use Matura\Exceptions\Exception as MaturaException;

use Twig_Loader_Filesystem;
use Twig_Environment;
use Twig_SimpleFilter;

function indent_width(Block $block)
{
    $level = $block->depth() - 1;
    return $level*2 - 1;
}

function indent($string, $amt = 3)
{
    if (empty($string)) {
        return '';
    } else {
        $indent = str_repeat(" ", $amt);
        return $indent.implode(explode("\n", $string), "\n".$indent);
    }
}

function tag($tag)
{
    $rest = array_slice(func_get_args(), 1);
    $text = implode($rest);
    return "<$tag>$text</$tag>";
}

/**
 * Contains test rendering methods.
 */
class Printer
{
    protected $options = array(
        'trace_depth' => 7
    );

    public function __construct($options = array())
    {
        $this->options = array_merge($this->options, $options);
    }

    public function onTestComplete(Event $event)
    {
        // ResultSet
        $index        = $event->result_set->totalTests();
        // TestMethod
        $indent_width = ($event->test->depth() - 1) * 2;
        $name         = $event->test->getName();
        // Result
        $style        = $event->result->getStatusString();
        $status       = $event->result->getStatus();

        $icon_map = array(
            Result::SUCCESS => '✓',
            Result::FAILURE => '✘',
            Result::SKIPPED => '○'
        );

        $icon = $icon_map[$status];

        $preamble = "$icon " . $index . ') ';
        $preamble = str_pad($preamble, $indent_width, " ", STR_PAD_RIGHT);

        return tag($style, $preamble) . $name;
    }

    public function onTestRunComplete(Event $event)
    {
        $parts = array(
            tag("bold", "Passed:"),
            "{$event->result_set->totalSuccesses()} of {$event->result_set->totalTests()}",
            tag("bold", "Skipped:"),
            "{$event->result_set->totalSuccesses()} of {$event->result_set->totalTests()}",
            tag("bold", "Assertions:"),
            "{$event->result_set->totalAssertions()}"
        );

        return implode(" ", $parts);
    }

    public function onSuiteStart(Event $event)
    {
        $path = $event->suite->path();
        return "<bold>Running: $path</bold>";
    }

    public function onSuiteComplete(Event $event)
    {
        return "";
    }

    public function onDescribeStart(Event $event)
    {
        $name = $event->describe->getName();
        $indent_width = ($event->describe->depth() - 1) * 2;
        return indent("<bold>Describe $name </bold>", $indent_width);
    }

    public function formatTrace(MaturaException $exception)
    {
        $trace = array_map(
            function ($trace) {
                $parts = array();
                if (isset($trace['file'])) {
                    $parts[] = $trace['file'].':'.$trace['line'];
                }
                if (isset($trace['function'])) {
                    $parts[] = $trace['function'].'()';
                }
                return implode(" ", $parts);
            },
            array_slice($exception->originalTrace(), 0, $this->options['trace_depth'])
        );
        return $trace;
    }

    public function renderEvent(Event $event)
    {
        $parts = array_map('ucfirst', array_filter(preg_split('/_|\./', $event->name)));
        $name = 'on'.implode($parts);

        if (is_callable(array($this, $name))) {
            return call_user_func(array($this, $name), $event);
        } else {
            return null;
        }
    }
}
