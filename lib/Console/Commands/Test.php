<?php namespace Matura\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Matura\Console\Output\Printer;

use Matura\Runners\TestRunner;
use Matura\Core\ResultSet;
use Matura\Core\ErrorHandler;

use Matura\Blocks\Suite;

use Matura\Events\Listener;
use Matura\Events\Event;
use Matura\Matura;

class Test extends Command implements Listener
{
    private $defaults = array(
        'trace_depth' => 7
    );

    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('Run tests')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The path to the file or directory to test.'
            )
            ->addOption(
                'grep',
                'g',
                InputOption::VALUE_REQUIRED,
                'Filter individual test cases by a description regexp.'
            )
            ->addOption(
                'include',
                'i',
                InputOption::VALUE_REQUIRED,
                'Include test files by a basename(filename) regexp.'
            )
            ->addOption(
                'exclude',
                'x',
                InputOption::VALUE_REQUIRED,
                'Exclude test files by a basename(filename) regexp.'
            )
            ->addOption(
                'trace_depth',
                'd',
                InputOption::VALUE_REQUIRED,
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
            'failure',
            new OutputFormatterStyle('red')
        );

        $output->getFormatter()->setStyle(
            'info',
            new OutputFormatterStyle('blue')
        );

        $output->getFormatter()->setStyle(
            'skipped',
            new OutputFormatterStyle('magenta')
        );

        $output->getFormatter()->setStyle(
            'incomplete',
            new OutputFormatterStyle('magenta')
        );

        $output->getFormatter()->setStyle(
            'u',
            new OutputFormatterStyle(null, null, array('underscore'))
        );

        $output->getFormatter()->setStyle(
            'suite',
            new OutputFormatterStyle('yellow', null)
        );

        $output->getFormatter()->setStyle(
            'bold',
            new OutputFormatterStyle('blue', null)
        );

        // Argument parsing
        // ################
        $path = $input->getArgument('path');

        $printer_options = array(
            'trace_depth' => $input->getOption('trace_depth') ?: $this->defaults['trace_depth']
        );

        // Configure Output Modules
        // ########################
        $printer = new Printer($printer_options);

        // Stash these for our event handler.
        $this->output = $output;
        $this->printer = $printer;

        $options = array();

        if ($include = $input->getOption('include')) {
            $options['include'] = "/$include/";
        }

        if ($match = $input->getOption('exclude')) {
            $options['exclude'] = "/$exclude/";
        }

        if ($grep = $input->getOption('grep')) {
            $options['grep'] = "/$grep/i";
        }

        // Bootstrap and Run
        // #################

        $test_runner = new TestRunner($path, $options);

        $test_runner->addListener($this);

        Matura::init();
        $code = $test_runner->run()->isSuccessful() ? 0 : 1;
        Matura::cleanup();

        return $code;
    }

    public function onMaturaEvent(Event $event)
    {
        $output = $this->printer->renderEvent($event);

        if ($output !== null) {
            $this->output->writeln($output);
        }
    }
}
