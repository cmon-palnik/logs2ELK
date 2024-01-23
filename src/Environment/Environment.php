<?php

namespace Logs2ELK\Environment;

use foroco\BrowserDetection;
use Logs2ELK\ConfigLoader;
use Logs2ELK\Environment\Type\Env;
use Logs2ELK\Environment\Type\Index;
use Logs2ELK\Exception;
use Logs2ELK\ExceptionCode as Code;

class Environment
{

    protected Env $env;
    protected Index $indexType;
    protected string $host = '';
    protected string $application = 'undefined';

    public function __construct(
        protected BrowserDetection $browser,
        protected ConfigLoader $loader
    )
    {
    }

    public function applyCLIArgs(string $indexType, string $envType, string $applicationName): void
    {
        try {
            $this->indexType = Index::from($indexType);
            $this->env = Env::from($envType);
        } catch (\Exception $e) {
            throw Exception::withCode(Code::BAD_ARGS_UNDEFINED_ENV_OR_INDEX_TYPE);

        }
        $this->application = $applicationName;
        $this->host = ConfigLoader::getHostname();
    }

    public function loadMapping(?string $indexType = null): array
    {
        $indexType = $indexType ?: $this->indexType->value;
        $mapping = $this->loader->loadYaml("mappings/$indexType.yml");
        if (!key_exists('properties', $mapping) || empty($mapping['properties'])) {
            throw Exception::withCode(Code::EMPTY_OR_BAD_MAPPING);
        }
        return $mapping;
    }

    public function buildIndexPrefix($indexType): string
    {
        return strtolower($indexType . '-' . $this->application . '-' . $this->env->value);
    }

    public function getIndexName()
    {
        return strtolower($this->indexType->value . '-' . $this->application . '-' . $this->env->value . "-" . $this->host . "-" . date("Y.W"));
    }

    public function getIndexParams($index)
    {
        return [
            'index' => $index,
            'body' => [
                'mappings' => $this->loadMapping()
            ]
        ];
    }

}
