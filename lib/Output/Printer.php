<?php namespace Matura\Output;

use Matura\Core\Result;
use Matura\Core\ResultSet;

use Twig_Loader_Filesystem;
use Twig_Environment;
use Twig_SimpleFilter;

class Printer
{
    protected static $default_options = array(
        'trace_depth' => 7
    );

    protected $options;

    public function __construct($options = array())
    {
        $this->options = array_merge(static::$default_options, $options);
    }

    public function render($template, $context)
    {
        $loader = new Twig_Loader_Filesystem(__DIR__.'/../../templates');

        $twig = new Twig_Environment($loader, array(
            'autoescape' => false
        ));

        $twig->addFilter(new Twig_SimpleFilter(
            'indent',
            function ($string, $amt = 3) {
                $indent = str_repeat(" ", $amt);
                $result = $indent.implode(explode("\n", trim($string)), "\n".$indent);
                return $result;
            }
        ));

        $twig->addFilter(new Twig_SimpleFilter(
            'pad_*_*',
            function ($direction, $amt, $string) {
                $dir_mapping = array(
                    'left'  => STR_PAD_LEFT,
                    'right' => STR_PAD_RIGHT,
                    'both'  => STR_PAD_BOTH,
                );
                return str_pad($string, $amt, " ", $dir_mapping[$direction]);
            }
        ));


        return $twig->render($template, $context);
    }

    public function renderResult(Result $result, ResultSet $result_set)
    {
        $context = array(
            'path'   => $result->getMethod()->path(),
            'status' => $result->getStatus(),
            'index'  => $result_set->totalTests() + 1
        );

        $exception = $result->getException();
        if ($exception) {
            $context['exception_message'] = $exception->getMessage();
            $context['exception_traces'] = $this->formatTrace($exception);
        }

        $status_mapping = array(
            '0' => 'result_failure.txt',
            '1' => 'result_skipped.txt',
            '2' => 'result_success.txt'
        );

        $template = $status_mapping[$result->getStatus()];

        return trim($this->render($template, $context));
    }

    public function renderSummary($result_set)
    {
        $context = array(
            'result_set' => $result_set
        );

        return $this->render('summary.txt', $context);
    }

    public function formatTrace($exception)
    {
        $trace = array_map(
            function ($trace) {
                $parts = array();
                if (isset($trace['file'])) {
                    $parts[] = $trace['file'];
                }
                if (isset($trace['line'])) {
                    $parts[] = 'L'.$trace['line'];
                }
                if (isset($trace['function'])) {
                    $parts[] = $trace['function'].'()';
                }
                return implode(" ", $parts);
            },
            array_slice($exception->getTrace(), 0, $this->options['trace_depth'])
        );
        return $trace;
    }
}
