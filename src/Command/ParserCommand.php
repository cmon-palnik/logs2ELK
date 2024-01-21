<?php

namespace Logs2ELK\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'logs2elk:parse',
    description: 'Parse log (from stdin) and index it in Elk',
    hidden: false,
)]
final class ParserCommand extends AbstractParserCommand
{

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $index = $this->parser->getIndexName();


        if (!$this->index->exists($index)) {
            $indexParams = $this->parser->getIndexParams($index);
            $this->index->create($indexParams);
        }

        while ($line = fgets(STDIN)) {
            if ($this->parser->excludeUserAgent($line)) {
                $output->writeln("EXCLUDED LOG: $line");
                continue;
            }

            $data = json_decode($line, true);
            if (!$data) {
                continue;
            }

            $data['message'] = $line;
            try {
                $this->index->put($index, $this->parser->parseLineByType($data));
            } catch (\Exception $ex) {
                $output->writeln($ex->getMessage());
                continue;
            }
        }

        $output->writeln('Done.');
        return Command::SUCCESS;
    }
}
