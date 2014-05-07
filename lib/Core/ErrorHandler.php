<?php namespace Matura\Core;

use Matura\Exceptions\Errors\ErrorException;

class ErrorHandler
{
    public function __construct($options = array())
    {
        $default_options = array(
            'error_reporting' => error_reporting()
        );

        $final_options = array_merge($default_options, $options);

        $this->error_reporting = $final_options['error_reporting'];
    }

    public function handleError($errno, $errstr, $errfile, $errline)
    {
        if ($errno & $this->error_reporting === 0) {
            return false;
        }

        $exception_class = $this->classForErrno($errno);
        throw new $exception_class($errno, $errstr, $errfile, $errline);
    }

    private function classForErrno($errno)
    {
        return 'ErrorException';
    }
}
