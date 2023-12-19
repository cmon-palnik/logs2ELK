<?php

namespace Logs2ELK\Command;

use Exception;
use Elastic\Elasticsearch\Client;
use Logs2ELK\Environment\EnvDefinition;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'logs2elk:sysstat',
    description: 'Update sysstat',
    hidden: false,
)]
class SysStatCommand extends Command
{

    public function __construct(
        private Client $client,
        private EnvDefinition $ed,
    )
    {
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        try {
            $index = $this->ed->getIndexName();

            if (!$this->client->indices()->exists(['index' => $index])) {
                $indexParams = $this->ed->getIndexParams($index);
                $this->client->indices()->create($indexParams);
            }

            try {
                $params = ['body' => $this->ed->parseLineByType($data), 'index' => $index];
                $response = $this->client->index($params);
            } catch (Exception $ex) {
                $output->writeln($ex->getMessage());
            }
        } catch (Exception $ex) {
            $msg = date("Y-m-d H:i:s") . " GLOBAL EXCEPTION " . PHP_EOL;
            $msg .= $ex->getMessage() . PHP_EOL;
            $msg .= $ex->getTraceAsString() . PHP_EOL;
            $output->writeln($msg);
        }
    }
}