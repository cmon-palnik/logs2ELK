<?php

namespace Logs2ELK\Report;

use Elastic\Elasticsearch\Client;
use Logs2ELK\ConfigLoader;

abstract class AbstractReport
{
    public static $results = 10000;
    public static $time_step = 60;
    public static $report_params = [];
    
    public string $filename = "";
    
    public function __construct(
        protected Client $client,
        protected ConfigLoader $loader
    )
    {
        $snake = $this->camelToSnake();
        $this->filename = $loader->getProjectDir('/var/report/') . $snake . '.json';
        $config = $loader->loadYaml("report/$snake.yml");
        static::set('results');
        static::set('time_step');
        static::set('report_params');
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

    public function getPart($param = null)
    {
        return [];
    }
    
    private function camelToSnake()
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', substr(get_class($this), strrpos(get_class($this), '\\') + 1)));
    }
    
    private static function set(int|string $var): void {
        if (isset($config[$var])) {
            static::$$var = $config[$var];
        }
    }
}