<?php

namespace Logs2ELK\Command;

use Logs2ELK\ConfigLoader;
use Logs2ELK\Environment\Type\Index as IndexType;
use Logs2ELK\ExceptionCode as Code;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsCommand(
    name: 'logs2elk:manage-indexes',
    description: 'Manage Elk indexes: get all, check, remove old ones',
    hidden: false,
)]
final class IndexManagerCommand extends AbstractParserCommand
{
    private $dates = [];
    private $allIndexesBaseParams = [];
    private $removeIndexes = [];
    private $checkIndexes = [];

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            parent::execute($input, $output);
        } catch (\Exception $ex) {
            if ($ex->is(Code::BAD_ARGS_UNDEFINED_ENV_OR_INDEX_TYPE)) {
                $output->writeln('NOTICE: This command ignores params as it scans all indexes.' . PHP_EOL);
            }
        }

        $this->getIndexes();
        $this->markIndexesToRemove();
        $this->checkIndexes();
        $this->removeOldIndexes();

        $output->writeln('Done.');
        return Command::SUCCESS;
    }

    private function getIndexes()
    {
        $this->writeln('Getting ELK indexes...');
        foreach (IndexType::values() as $index) {
            $indexPrefix = $this->parser->buildIndexPrefix($index) . "*";
            $indexes = $this->index->getIndexesByName($indexPrefix);

            if (!empty($indexes)) {
                $this->sortIndexes($indexes);
                $this->writeln("-> Found " . count($indexes) . " indexes for pattern: " . $indexPrefix);
            } else {
                $this->writeln("-> No indexes for pattern: " . $indexPrefix);
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
        $allowedDates = [ date("Y.W") ];
        for ($i = 1; $i < ConfigLoader::getWeeksToKeep(); $i++) {
            $allowedDates[] = date("Y.W", strtotime("-{$i} week"));
        }

        foreach ($this->dates as $date => $indexes) {
            if (!in_array($date, $allowedDates)) {
                $this->removeIndexes = array_merge($this->removeIndexes, array_keys($indexes));
            } else {
                $this->checkIndexes = array_merge($this->checkIndexes, array_keys($indexes));
            }
        }
        $this->writeln('Old indexes marked to remove: ' . count($this->removeIndexes));
    }

    private function checkIndexes()
    {
        foreach ($this->checkIndexes as $index) {
            $this->write("Checking index $index..");
            $indexBaseParams = $this->allIndexesBaseParams[$index]['baseParams'];

            $configMapping = $this->parser->loadMapping($indexBaseParams[0]);
            $mapping = $this->index->getMapping($index);

            $nm = $mapping[$index]['mappings']['properties']['time'];
            $sm = $configMapping['properties']['time'];

            $diff = array_diff_assoc($sm, $nm);

            if (!empty($diff)) {
                $this->removeIndexes[] = $index;
                $this->writeln('. marked to delete.');
            } else {
                $this->writeln('.');
            }
        }
    }

    private function removeOldIndexes()
    {
        foreach ($this->removeIndexes as $index) {
            $this->write("Deleting index $index");
            $this->index->delete($index);
            $this->writeln('.');
        }
    }

}
