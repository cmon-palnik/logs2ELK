<?php

namespace Logs2ELK\Command;

use Logs2ELK\ConfigLoader;
use Logs2ELK\Environment\Environment;
use Logs2ELK\Environment\EnvironmentTrait;
use Logs2ELK\ElasticGateway\Index;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractEnvironmentCommand extends AbstractCommand
{

    use EnvironmentTrait;

    public function __construct(
        protected Index       $index,
        protected Environment $env,
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $this->env->applyCLIArgs($input);
        return Command::SUCCESS;
    }

    public function configure(): void
    {
        $this->addConstrainedArgument(
            'indexType',
            'index',
            ConfigLoader::getArgDefault('INDEX_TYPE'),
            $this->indexes
        );
        $this->addConstrainedArgument(
            'envType',
            'environment',
            ConfigLoader::getArgDefault('ENV_TYPE'),
            $this->envs
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
