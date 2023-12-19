<?php

namespace Logs2ELK\Command;

use Elastic\Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Logs2ELK\Environment\EnvDefinition;

#[AsCommand(
    name: 'logs2elk:manage-index',
    description: 'Manage Elasticsearch indexes.',
    hidden: false,
)]
class IndexManagerCommand extends Command
{
    private $dates = [];
    private $allIndexesBaseParams = [];
    private $removeIndexes = [];
    private $checkIndexes = [];

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
            $this->getIndexes();
            $this->markIndexesToRemove();
            $this->checkIndexes();
            $this->removeOldIndexes();
        } catch (\Exception $ex) {
            $msg = date("Y-m-d H:i:s") . " GLOBAL EXCEPTION " . PHP_EOL;
            $msg .= $ex->getMessage() . PHP_EOL;
            $msg .= $ex->getTraceAsString() . PHP_EOL;
            $output->writeln($msg);
        }

        return Command::SUCCESS;
    }

    private function getIndexes()
    {
        foreach ($this->ed->indexes as $index) {
            $indexPrefix = $this->ed->buildIndexPrefix($index) . "*";
            $indexes = $this->client->cat()->indices(array('index' => $indexPrefix));
            if (!empty($indexes)) {
                $this->sortIndexes($indexes);
                echo "found " . count($indexes) . " indexes for pattern:" . $indexPrefix . PHP_EOL;
            } else {
                echo "no indexes for pattern:" . $indexPrefix . PHP_EOL;
            }
        }
    }

    private function sortIndexes($indexes)
    {
        foreach ($indexes as $index) {
            $indexParams = explode("-", $index['index']);
            $this->dates[end($indexParams)][$index['index']] = $index;
            $this->allIndexesBaseParams[$index['index']]['baseParams'] = $indexParams;
        }
    }

    private function markIndexesToRemove()
    {
        $allowedDates = [
            date("Y.W"),
            date("Y.W", strtotime("-1 week")),
            date("Y.W", strtotime("-2 week")),
        ];

        foreach ($this->dates as $date => $indexes) {
            if (!in_array($date, $allowedDates)) {
                $this->removeIndexes = array_merge($this->removeIndexes, array_keys($indexes));
            } else {
                $this->checkIndexes = array_merge($this->checkIndexes, array_keys($indexes));
            }
        }
    }

    private function removeOldIndexes()
    {
        foreach ($this->removeIndexes as $index) {
            echo "deleting index $index" . PHP_EOL;
            $this->client->indices()->delete(['index' => $index]);
        }
    }

    private function checkIndexes()
    {
        foreach ($this->checkIndexes as $index) {
            echo "checking index $index" . PHP_EOL;
            $indexBaseParams = $this->allIndexesBaseParams[$index]['baseParams'];
            $configMapping = \App\ConfigurationManager::loadMappingConfig(__DIR__ . "/../config/mapping-" . $indexBaseParams[0] . ".yml");
            //$configMapping = json_decode(file_get_contents(__DIR__ . "/../config/mapping-" . $indexBaseParams[0] . ".json"), true);
            $mapping = $this->client->indices()->getMapping(['index' => $index]);
            $nm = $mapping[$index]['mappings']['properties']['time'];
            $sm = $configMapping['properties']['time'];
            $diff = array_diff_assoc($sm, $nm);
            if (!empty($diff)) {
                $this->removeIndexes[] = $index;
            }
        }
    }
}