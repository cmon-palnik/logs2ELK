<?php

namespace Logs2ELK\Command;

use Logs2ELK\ConfigLoader;
use Logs2ELK\Environment\Parser;
use Logs2ELK\Environment\Type\Env;
use Logs2ELK\Environment\Type\Index as IndexType;
use Logs2ELK\ElasticGateway\Index;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractParserCommand extends AbstractCommand
{

    public function __construct(
        protected Index  $index,
        protected Parser $parser,
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $this->parser->applyCLIArgs(
            $input->getArgument('indexType'),
            $input->getArgument('envType'),
            $input->getArgument('applicationName')
        );
        return Command::SUCCESS;
    }

    public function configure(): void
    {
        $this->addConstrainedArgument(
            'indexType',
            'index',
            ConfigLoader::getArgDefault('INDEX_TYPE'),
            IndexType::values()
        );
        $this->addConstrainedArgument(
            'envType',
            'environment',
            ConfigLoader::getArgDefault('ENV_TYPE'),
            Env::values()
        );
        $this->addArgument(
            'applicationName',
            InputArgument::OPTIONAL,
            'Name of the logged application. Default: undefined',
            ConfigLoader::getArgDefault('APPLICATION_NAME'),
        );
    }

    private function addConstrainedArgument(string $name, string $description, mixed $default, array $availableValues): void
    {
        $this->addArgument(
            $name,
            InputArgument::OPTIONAL,
            "Type of the {$description}. Default: " . $default .
                'Available values: ' . implode(', ', $availableValues) . '.',
            $default,
            function (CompletionInput $input) use ($availableValues): array {
                return $this->getCompletionValue(
                    $input->getCompletionValue(),
                    $availableValues
                );
            }
        );
    }

    private function getCompletionValue(string $currentValue, $availableValues)
    {
        $results = $availableValues;
        foreach ($availableValues as $key => $value) {
            if (!str_starts_with($value, $currentValue)) {
                unset($results[$key]);
            }
        }
        return $results;
    }
}
