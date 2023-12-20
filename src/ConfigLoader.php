<?php

namespace Logs2ELK;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigLoader
{

    public function __construct(
        private KernelInterface $appKernel
    )
    {
    }

    public function loadYaml(string $filename, ?string $path = null): array
    {
        $path = $this->getPath($path);
        $resource = Yaml::parse(file_get_contents($path . $filename));
        return $resource ?? [];
    }

    public function loadJson(string $filename, ?string $path = null): array
    {
        $path = $this->getPath($path);
        $resource = json_decode(file_get_contents($path . $filename), true);
        return $resource;
    }

    public function getProjectDir(?string $path = null): string
    {
        return $this->appKernel->getProjectDir() . $path ?? '/';
    }

    private function getPath($path = null): string
    {
        return $this->getProjectDir($path ?? '/config/');
    }

    public static function getTimeFormat(): string
    {
        return $_ENV['TIME_FORMAT'];
    }

}
