<?php

namespace Logs2ELK\Report;

use Logs2ELK\ConfigLoader;
use Logs2ELK\Gateway\Search;
use Logs2ELK\GeneralException;
use Logs2ELK\GeneralExceptionCode as Code;
use Logs2ELK\Command\OutputInterfaceTrait;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractReport
{
    use OutputInterfaceTrait;

    public static int $results = 10000;
    public static int $time_step = 60;
    public static array $report_params = [];

    public string $filename = "";
    protected int $lastResults = 0;

    protected string $dateFrom;
    protected ?string $dateTo;

    public function __construct(
        protected Search $index,
        protected ConfigLoader $loader,
    )
    {
        $snake = $this->camelToSnake();
        $this->filename = $loader->getProjectDir('/var/report/') . $snake . '.json';
        $config = $loader->loadYaml("report/$snake.yml");
        static::set('results', $config);
        static::set('time_step', $config);
        static::set('report_params', $config);
    }

    public function setReportDates(InputInterface $input): self
    {
        $this->dateFrom = $input->getArgument('dateFrom');
        $this->dateTo = $input->getArgument('dateTo');
        return $this;
    }

    protected function search($params): array
    {
        return $this->index->search($params);
    }

    protected function gmdate(int $time): string
    {
        return gmdate(ConfigLoader::getTimeFormat(), $time);
    }

    public function read()
    {
        return json_decode(file_get_contents($this->filename), true);
    }

    public function generate($requests)
    {
        $result = [];
        foreach ($requests as $requestURI => $count) {
            $result[$requestURI] = $this->getPart($requestURI);
        }
        file_put_contents($this->filename, json_encode($result));
        return $result;
    }

    private function camelToSnake()
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', substr(get_class($this), strrpos(get_class($this), '\\') + 1)));
    }

    private static function set(int|string $var, array $config): void {
        if (isset($config[$var])) {
            static::$$var = $config[$var];
        }
    }

}
