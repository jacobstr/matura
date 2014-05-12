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

function pad_left($length, $string, $char = ' ')
{
    return str_pad($string, $length, $char, STR_PAD_LEFT);
}

function pad_right($length, $string, $char = ' ')
{
    return str_pad($string, $length, $char, STR_PAD_RIGHT);
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
        $preamble = pad_right($indent_width, $preamble, " ");

        return tag($style, $preamble) . $name;
    }

    public function onTestRunComplete(Event $event)
    {
        $summary = array(
            tag("bold", "Passed:"),
            "{$event->result_set->totalSuccesses()} of {$event->result_set->totalTests()}",
            tag("bold", "Skipped:"),
            "{$event->result_set->totalSkipped()} of {$event->result_set->totalTests()}",
            tag("bold", "Assertions:"),
            "{$event->result_set->totalAssertions()}"
        );

        // The Passed / Failed / Skipped summary
        $summary = implode(" ", $summary);

        // Error formatting.
        $failures = $event->result_set->getFailures();
        $failure_count = count($failures);

        $index = 0;
        $result = array();
        foreach ($failures as $failure) {
            $index++;
            $result[] = $this->formatFailure($index, $failure);
        }

        return $summary . "\n\n" . implode("\n\n", $result);
    }

    public function onSuiteStart(Event $event)
    {
        $label = "Running: ".$event->suite->path();
        $length = strlen($label);

        return tag("bold", $label."\n").str_repeat("-", $length);
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

    // Formatting helpers
    // ##################

    protected function formatFailure(Result $failure)
    {
        $exception = $failure->getException();
        $exception_category = $failure->getException()->getCategory();

        return implode(
            "\n",
            array(
                tag("failure", $failure->getBlock()->path()),
                tag("info", $exception_category.': ') . $exception->getMessage(),
                tag("info", "Via:"),
                $this->formatTrace($exception)
            )
        );
    }

    public function formatTrace(MaturaException $exception)
    {
        $index = 0;
        $result = array();
        $sliced_trace = array_slice($exception->originalTrace(), 0, $this->options['trace_depth']);

        foreach ($sliced_trace as $trace) {
            $index++;

            $parts = array(pad_right(4, $index.")"));

            if (isset($trace['file'])) {
                $parts[] = $trace['file'].':'.$trace['line'];
            }
            if (isset($trace['function'])) {
                $parts[] = $trace['function'].'()';
            }
            $result[] = implode($parts);
        }

        return indent(implode("\n", $result));
    }

    /**
     * Conducts our 'event_group.action' => 'onEventGroupAction delegation'
     */
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
