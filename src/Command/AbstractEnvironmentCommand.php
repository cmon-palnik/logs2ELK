<?php

namespace Logs2ELK\Command;

use Elastic\Elasticsearch\Client;
use Logs2ELK\Environment\EnvDefinition;
use Logs2ELK\Environment\EnvironmentTrait;
use Logs2ELK\WriteToOutputTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractEnvironmentCommand extends AbstractCommand
{

    use EnvironmentTrait;

    public function __construct(
        protected Client $client,
        protected EnvDefinition $ed,
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $this->ed->applyCLIArgs($input);
        return Command::SUCCESS;
    }

    public function configure()
    {
        $this->addConstrainedArgument(
            'indexType',
            'index',
            self::INDEX,
            $this->indexes
        );
        $this->addConstrainedArgument(
            'envType',
            'environment',
            self::E_DEV,
            $this->envs
        );
        $this->addArgument(
            'applicationName',
            InputArgument::OPTIONAL,
            'Name of the application. Default: undefined',
            'undefined'
        );
    }

    private function addConstrainedArgument(string $name, string $description, mixed $default, array $availableValues): void
    {
        $this->addArgument(
            $name,
            InputArgument::OPTIONAL,
            "Type of the {$description}. Default: " . self::INDEX .
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
