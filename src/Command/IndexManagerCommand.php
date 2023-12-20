<?php

namespace Logs2ELK\Command;

use Elastic\Elasticsearch\Client;
use Logs2ELK\ConfigLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Logs2ELK\Environment\EnvDefinition;

#[AsCommand(
    name: 'logs2elk:manage-index',
    description: 'Manage Elasticsearch indexes',
    hidden: false,
)]
class IndexManagerCommand extends AbstractEnvironmentCommand
{
    private $dates = [];
    private $allIndexesBaseParams = [];
    private $removeIndexes = [];
    private $checkIndexes = [];

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->getIndexes();
        $this->markIndexesToRemove();
        $this->checkIndexes();
        $this->removeOldIndexes();

        return Command::SUCCESS;
    }

    private function getIndexes()
    {
        foreach ($this->ed->indexes as $index) {
            $indexPrefix = $this->ed->buildIndexPrefix($index) . "*";
            $indexes = $this->client->cat()->indices(array('index' => $indexPrefix))->asArray();
            if (!empty($indexes)) {
                $this->sortIndexes($indexes);
                $this->writeln("found " . count($indexes) . " indexes for pattern:" . $indexPrefix);
            } else {
                $this->writeln("no indexes for pattern:" . $indexPrefix);
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
            $this->writeln("deleting index $index");
            $this->client->indices()->delete(['index' => $index]);
        }
    }

    private function checkIndexes()
    {
        foreach ($this->checkIndexes as $index) {
            $this->writeln("checking index $index");
            $indexBaseParams = $this->allIndexesBaseParams[$index]['baseParams'];
            $configMapping = $this->ed->loadMapping($indexBaseParams[0]);
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
