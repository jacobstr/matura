<?php namespace Matura\Runners;

use SplFileInfo;
use Matura\Matura;
use Matura\Blocks\Suite;
use Matura\Blocks\Describe;
use Matura\Blocks\Methods\TestMethod;

use Matura\Core\ResultSet;
use Matura\Core\Result;
use Matura\Core\InvocationContext;

use Matura\Events\Listener;
use Matura\Events\Emitter;

use ArrayIterator;
use RegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FileSystemIterator;

/**
 * Responsible for invoking files, Suites, and TestMethods.
 *
 * The test envirojnment is set up mostly in #run() where we register our
 * error handler and load our DSL.
 */
class TestRunner extends Runner
{
    protected $options = array(
        'filter' => '//',
        'grep' => '//'
    );

    /** @var The directory or folder containing our test file(s). */
    protected $path;

    protected $result_set;

    public function __construct($path, $options = array())
    {
        $this->path = $path;
        $this->options = array_merge($this->options, $options);
        $this->result_set = new ResultSet();
    }

    /**
     * Recursively obtains all test files under `$this->path` and returns
     * the filtered result after applying our filtering regex.
     *
     * @return Iterator
     */
    public function collectFiles()
    {
        if (is_dir($this->path)) {
            $directory = new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS );
            $iterator = new RecursiveIteratorIterator($directory);
            return new RegexIterator($iterator, $this->options['filter']);
        } else {
            return new ArrayIterator(array(new SplFileInfo($this->path)));
        }
    }

    /**
     * Bootstraps parts of our test enviornment and iteratively invokes each
     * file.
     *
     * @return ResultSet
     */
    public function run()
    {
        $tests = $this->collectFiles();

        $this->emit('test_run.start', array('result_set' => $this->result_set));

        foreach ($tests as $test_file) {
            $suite = new Suite(
                new InvocationContext(),
                function () use ($test_file) {
                    require $test_file;
                },
                $test_file->getPathName()
            );

            $suite->build();

            $suite_runner = new SuiteRunner($suite, new ResultSet());

            // Forward my listeners.
            foreach ($this->listeners as $listener) {
                $suite_runner->addListener($listener);
            }

            $suite_runner->run();
        }

        $this->emit('test_run.complete', array('result_set' => $this->result_set));

        return $this->result_set;
    }
}
