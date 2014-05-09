<?php namespace Matura\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Matura\Console\Output\Printer;

use Matura\Core\TestContext;
use Matura\Core\TestRunner;
use Matura\Core\ResultSet;
use Matura\Core\ErrorHandler;

use Matura\Blocks\Suite;

use Matura\Events\Listener;
use Matura\Matura;

class Test extends Command implements Listener
{
    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('Run tests.')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The path to the file or directory to test.'
            )
            ->addOption(
                'grep',
                'g',
                InputOption::VALUE_OPTIONAL,
                'Filter individual test cases by a description regexp.'
            )
            ->addOption(
                'filter',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Filter test files by a filename regexp.'
            )
            ->addOption(
                'trace_depth',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Set the depth of printed stack traces.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->getFormatter()->setStyle(
            'success',
            new OutputFormatterStyle('green')
        );

        $output->getFormatter()->setStyle(
            'fail',
            new OutputFormatterStyle('red')
        );

        $output->getFormatter()->setStyle(
            'info',
            new OutputFormatterStyle('blue')
        );

        $output->getFormatter()->setStyle(
            'skip',
            new OutputFormatterStyle('magenta')
        );

        $output->getFormatter()->setStyle(
            'u',
            new OutputFormatterStyle(null, null, array('underscore'))
        );

        $output->getFormatter()->setStyle(
            'bold',
            new OutputFormatterStyle('yellow')
        );

        // Argument parsing
        // ################
        $path = $input->getArgument('path');

        $printer_options = array(
            'trace_depth' => $input->getOption('trace_depth') ?: 7
        );

        $filter = $input->getOption('filter') ?: '/.*/i';

        // Configure Output Modules
        // ########################
        $printer = new Printer($printer_options);

        // Stash these for our event handler.
        $this->output = $output;
        $this->printer = $printer;

        $test_runner = new TestRunner();
        $test_runner->addListener($this);

        // Bootstrap and Run
        // #################

        $error_handler = new ErrorHandler();

        set_error_handler(array($error_handler, 'handleError'));

        require_once __DIR__ . '/../../functions.php';

        if (is_dir($path)) {
            $directory = new \RecursiveDirectoryIterator($path);
            $iterator = new \RecursiveIteratorIterator($directory);
            $tests = new \RegexIterator($iterator, '/.*test.*php/');
        } else {
            $tests = array($path);
        }

        $result_set = new ResultSet();

        foreach($tests as $test) {
            Suite::clear();
            require $test;
            $test_runner->runSuite( Suite::getLastSuite(), $result_set);
        }

        $this->output->writeln(
            $this->printer->renderSummary($result_set)
        );

        restore_error_handler();

        return $result_set->exitCode();
    }

    public function onMaturaEvent($name, $args)
    {
        if ($name === 'test.complete') {
            $this->output->writeln(
                $this->printer->renderResult($args[0], $args[1])
            );
        }
    }
}
