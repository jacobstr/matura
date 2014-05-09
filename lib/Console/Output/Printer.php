<?php namespace Matura\Console\Output;

use Matura\Core\Result;
use Matura\Core\ResultSet;
use Matura\Blocks\Suite;
use Matura\Exceptions\Exception as MaturaException;

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

        $loader = new Twig_Loader_Filesystem(__DIR__.'/../../../templates');

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

        $this->twig = $twig;
    }

    public function render($template, $context)
    {
        return $this->twig->render($template, $context);
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
            $context['exception_message']  = $exception->getMessage();
            $context['exception_category'] = $exception->getCategory();
            $context['exception_traces']   = $this->formatTrace($exception);
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

    public function renderStart(Suite $suite, ResultSet $result_set)
    {
        $context = array(
            'suite' => $suite,
            'result_set' => $result_set,
        );

        return $this->render('test_start.txt', $context);
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
}
