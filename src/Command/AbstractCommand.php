<?php

namespace Logs2ELK\Command;

use Logs2ELK\OutputInterfaceTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractCommand extends Command
{
    use OutputInterfaceTrait;

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setOutputInterface($output);
        $this->setOutputInterfaceToServices();
        return Command::SUCCESS;
    }
}
