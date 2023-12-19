<?php

namespace Logs2ELK\Command;

use Exception;
use Logs2ELK\Report\AverageClientToProxy;
use Logs2ELK\Report\Requests;
use Logs2ELK\Report\Statuses;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'logs2elk:report',
    description: 'Generate report',
    hidden: false,
)]
class ReportCommand extends Command
{


    public function __construct(
        private Requests $requests,
        private Statuses $statuses,
        private AverageClientToProxy $averageClientToProxy,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        try {

            $requests = $this->requests->requests();
            $statuses = $this->statuses->generate($requests);
            $this->averageClientToProxy->generate($requests);
            
            $output->writeln('DONE');
        } catch (Exception $ex) {
            $msg = date("Y-m-d H:i:s") . " GLOBAL EXCEPTION " . PHP_EOL;
            $msg .= $ex->getMessage() . PHP_EOL;
            $msg .= $ex->getTraceAsString() . PHP_EOL;
            $output->writeln($msg);
        }
    }
}
