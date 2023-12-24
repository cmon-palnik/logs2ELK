<?php

namespace Logs2ELK\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'logs2elk:sysstat',
    description: 'Parse and index current sysstat',
    hidden: false,
)]
class SysStatCommand extends AbstractEnvironmentCommand
{

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $index = $this->env->getIndexName();

        if (!$this->index->exists($index)) {
            $indexParams = $this->env->getIndexParams($index);
            $this->index->create($indexParams);
        }
        $output->writeln('Sending a line to ELK...');
        $this->index->put($index, $this->env->parseLineByType());

        $output->writeln('Done.');
        return Command::SUCCESS;
    }
}
