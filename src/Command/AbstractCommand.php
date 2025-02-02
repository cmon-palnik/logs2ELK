<?php

namespace Logs2ELK\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    use OutputInterfaceTrait;

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setOutputInterface($output);
        return Command::SUCCESS;
    }
}
