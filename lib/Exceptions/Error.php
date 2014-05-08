<?php namespace Matura\Exceptions;

use Matura\Exceptions\Exception;

/**
 * Used when we generated Exceptions via PHP's basic error mechanism.
 */
class Error extends Exception
{
    // http://www.php.net/manual/en/errorfunc.constants.php
    public static $error_names = array(
        /*     1 */ E_ERROR             => 'E_ERROR',
        /*     2 */ E_WARNING           => 'E_WARNING',
        /*     4 */ E_PARSE             => 'E_PARSE',
        /*     8 */ E_NOTICE            => 'E_NOTICE',
        /*    16 */ E_CORE_ERROR        => 'E_CORE_ERROR',
        /*    32 */ E_CORE_WARNING      => 'E_CORE_WARNING',
        /*    64 */ E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        /*   128 */ E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
        /*   256 */ E_USER_ERROR        => 'E_USER_ERROR',
        /*   512 */ E_USER_WARNING      => 'E_USER_WARNIng',
        /*  1024 */ E_USER_NOTICE       => 'E_USER_NOTICE',
        /*  2048 */ E_STRICT            => 'E_STRICT',
        /*  4096 */ E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        /*  8192 */ E_DEPRECATED        => 'E_DEPRECATED',
        /* 16384 */ E_USER_DEPRECATED   => 'E_USER_DEPRECATED'
    );

    protected $errno;
    protected $errstr;
    protected $errfile;
    protected $errline;

    public function __construct($errno, $errstr, $errfile, $errline)
    {
        $this->errno   = $errno;
        $this->errstr   = $errstr;
        $this->errfile = $errfile;
        $this->errline = $errline;

        $this->message = $this->errstr;
    }

    public function getCategory()
    {
        return 'Error '.static::$error_names[$this->errno];
    }

    public function originalTrace()
    {
        return array(array(
            'file' => $this->errfile,
            'line' => $this->errline
        ));
    }
}
