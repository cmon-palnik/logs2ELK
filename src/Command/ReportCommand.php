<?php

namespace Logs2ELK\Command;

use Logs2ELK\ConfigLoader;
use Logs2ELK\Report\AverageClientToProxy;
use Logs2ELK\Report\Requests;
use Logs2ELK\Report\Statuses;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'logs2elk:report',
    description: 'Generate reports',
    hidden: false,
)]
class ReportCommand extends AbstractCommand
{

    public function __construct(
        private Requests $requests,
        private Statuses $statuses,
        private AverageClientToProxy $averageClientToProxy,
    )
    {
        parent::__construct();
    }

    public function configure()
    {
        $today = new \DateTime();
        $format = ConfigLoader::getTimeFormat();
        $this->addArgument(
            'dateFrom',
            InputArgument::OPTIONAL,
            'From (format: ' . $format . '). Default: first day of month',
            $today->modify("first day of this month 0:0")->format($format)
        );
        $this->addArgument(
            'dateTo',
            InputArgument::OPTIONAL,
            'To (format: ' . $format . '). Default: now',
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $requests = $this->requests->setReportDates($input)->requests();

        $this->statuses->setReportDates($input)->generate($requests);
        $this->averageClientToProxy->setReportDates($input)->generate($requests);

        $output->writeln('DONE');

        return Command::SUCCESS;
    }
}
