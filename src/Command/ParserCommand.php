<?php

namespace Logs2ELK\Command;

use Elastic\Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Logs2ELK\Environment\EnvDefinition;

#[AsCommand(
    name: 'logs2elk:parse',
    description: 'Parse logs and index them in Elasticsearch',
    hidden: false,
)]
class ParserCommand extends Command
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

            try {

                if (!$this->client->indices()->exists(['index' => $index])) {
                    $indexParams = $this->ed->getIndexParams($index);
                    $this->client->indices()->create($indexParams);
                }
            } catch (\Exception $ex) {
                $output->writeln("Elastic server issue, please check host, or index creation");
                $output->writeln($ex->getMessage());
                return Command::FAILURE;
            }

            while ($line = fgets(STDIN)) {
                if ($this->ed->excludeUA($line)) {
                    $output->writeln("EXCLUDED LOG: $line");
                    continue;
                }
                $data = json_decode($line, true);
                if (!$data) {
                    continue;
                }
                $data['message'] = $line;
                try {
                    $params = ['body' => $this->ed->parseLineByType($data), 'index' => $index];
                    $response = $this->client->index($params);
                } catch (\Exception $ex) {
                    $output->writeln($ex->getMessage());
                    continue;
                }
            }
        } catch (\Exception $ex) {
            $msg = date("Y-m-d H:i:s") . " GLOBAL EXCEPTION " . PHP_EOL;
            $msg .= $ex->getMessage() . PHP_EOL;
            $msg .= $ex->getTraceAsString() . PHP_EOL;
            $output->writeln($msg);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}