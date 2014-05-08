<?php namespace Matura\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

use Matura\Matura;

class ExportDSL extends Command
{
    protected function configure()
    {
        $this
            ->setName('export-dsl')
            ->setDescription('Regenerates the DSL workaround file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Matura::exportDSL();
    }
}
