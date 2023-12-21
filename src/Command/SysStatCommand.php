<?php

namespace Logs2ELK\Command;

use Exception;
use Elastic\Elasticsearch\Client;
use Logs2ELK\Environment\Environment;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'logs2elk:sysstat',
    description: 'Update sysstat',
    hidden: false,
)]
class SysStatCommand extends AbstractEnvironmentCommand
{

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $index = $this->ed->getIndexName();

        if (!$this->client->indices()->exists(['index' => $index])) {
            $indexParams = $this->ed->getIndexParams($index);
            $this->client->indices()->create($indexParams);
        }

        try {
            $params = ['body' => $this->ed->parseLineByType(), 'index' => $index];
            $response = $this->client->index($params);
            $output->writeln($response);

        } catch (Exception $ex) {
            $output->writeln($ex->getMessage());
        }

        return Command::SUCCESS;
    }
}
