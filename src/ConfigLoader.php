<?php

namespace Logs2ELK;

use Logs2ELK\Environment\Type\Env;
use Logs2ELK\Environment\Type\Index;
use Logs2ELK\ExceptionCode as Code;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigLoader
{

    private const ENV_DEFAULTS = [
        'DEFAULT_INDEX_TYPE' => Index::INDEX,
        'DEFAULT_ENV_TYPE' => Env::DEV,
        'DEFAULT_APPLICATION_NAME' => 'defaultApp',
        'TIME_FORMAT' => 'Y-m-d H:i:s O',
        'WEEKS_TO_KEEP' => 3
    ];

    public function __construct(
        private KernelInterface $appKernel
    )
    {
    }

    public function loadYaml(string $filename, ?string $path = null): array
    {
        $path = $this->getConfigPath($path);
        $resource = Yaml::parse(file_get_contents($path . $filename));
        return $resource ?? [];
    }

    public function loadJson(string $filename, ?string $path = null): array
    {
        $path = $this->getConfigPath($path);
        $resource = json_decode(file_get_contents($path . $filename), true);
        return $resource;
    }

    public function getProjectDir(?string $path = null): string
    {
        return $this->appKernel->getProjectDir() . $path ?? '/';
    }

    private function getConfigPath($path = null): string
    {
        return $this->getProjectDir($path ?? '/config/');
    }

    private static function getENV(string $L2E_ENV): string|null
    {
        $key = "L2E_$L2E_ENV";
        $default = self::ENV_DEFAULTS[$L2E_ENV];
        if ($default instanceof Index || $default instanceof Env) {
            $default = $default->value;
        }
        return $_ENV[$key] ?? $default ?? null;
    }

    public static function getTimeFormat(): string
    {
        return self::getENV('TIME_FORMAT');
    }

    public static function getWeeksToKeep(): int
    {
        $weeks = (int) self::getENV('WEEKS_TO_KEEP');
        return $weeks > 0 ? $weeks : 1;
    }

    public static function getArgDefault(string $L2E_DEFAULT_ENV): string
    {
        $result = self::getENV("DEFAULT_$L2E_DEFAULT_ENV");
        if (is_null($result)) {
            throw Exception::withCode(Code::MISSING_ENV_VAR, ['var' => "DEFAULT_$L2E_DEFAULT_ENV"]);
        }
        return $result;
    }

    public static function getHostname(): string
    {
        $host = trim(self::getENV('DEFAULT_HOST') ?? '');
        return !empty($host) ? $host : (explode(".", gethostname())[0] ?? 'l2e_config_err');
    }

}
