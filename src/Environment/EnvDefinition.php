<?php

namespace Logs2ELK\Environment;

use Exception;
use foroco\BrowserDetection;
use Logs2ELK\ConfigLoader;

class EnvDefinition
{
    use EnvironmentTrait;

    private $env = '';
    private $indexType = '';
    private $host = '';

    public static $mapErrorFields = [
        'type', 'message', 'file', 'line', 'trace'
    ];

    private $application = 'undefined';

    public function __construct(
        private BrowserDetection $browser,
        private ConfigLoader $loader
    )
    {
        global $argv;
        $type = isset($argv[1]) ? $argv[1] : self::INDEX;
        if (!in_array($type, $this->indexes)) {
            throw new Exception("Unknown index type: $type; allowed: " . json_encode($this->indexes) . PHP_EOL);
        }
        $this->indexType = $type;
        $env = isset($argv[2]) ? $argv[2] : self::E_DEV;
        if (!in_array($env, $this->envs)) {
            throw new Exception("Unknown env type: $env; allowed: " . json_encode($this->envs) . PHP_EOL);
        }
        $this->application = isset($argv[3]) ? $argv[3] : "undefined";
        $this->env = $env;
        $this->host = explode(".", gethostname())[0];
    }

    public function buildIndexPrefix($indexType){
        return strtolower($indexType . '-' . $this->application . '-' . $this->env);
    }

    public function getIndexName()
    {
        return strtolower($this->indexType . '-' . $this->application . '-' . $this->env . "-" . $this->host . "-" . date("Y.W"));
    }

    public function getIndexParams($index)
    {
        $mapping = $this->loader->loadYaml("mappings/{$this->indexType}.yml");
        return [
            'index' => $index,
            'body' => [
                'mappings' => $mapping['properties'], //json_decode(file_get_contents(__DIR__ . "/config/mapping-" . $this->indexType . ".json"), true)
            ]
        ];
    }

    public function parseLineByType($data)
    {
        if (isset($data['datetime'])) {
            $data['time'] = $data['datetime'];
            $data['HeaderXForwardedFor'] = "0.0.0.0:0";
        }
        $data['application'] = $this->application;
        $data['env'] = $this->env;
        $data['node'] = $this->host;
        $data['time'] = date(self::TIMEFORMAT);
        $data['client'] = $this->getClientFromForwarded($data['HeaderXForwardedFor']);
        
        switch ($this->indexType) {
            default:
            case self::INDEX:
            case self::WPINDEX:
                if (isset($data['logLevel'])) {
                    throw new Exception("Not an access log");
                }
                return $this->parseLineTraffic($data);
            case self::WPERROR:
            case self::ERROR:
                if (!isset($data['logLevel'])) {
                    throw new Exception("Not an error log");
                }
                return $this->parseLineError($data);
            case self::APPMSG:
                if (!isset($data['level_name'])) {
                    throw new Exception("Not an error log");
                }
                return $this->parseLineMsg($data);
            case self::APPSYS:
                return $this->parseLineSys($data);
        }
    }

    public function parseLineTraffic($data)
    {
        $data['deviceType'] = $this->browser->getOS($data['userAgent'])['os_type'];
        return $data;
    }

    public function parseLineError($data)
    {
        $data['errorType'] = $data['logLevel'];
        $data['inFile'] = "";
        $data['inLine'] = "0";
        $data['stackTrace'] = "";
        $data['wpType'] = "";
        $data['wpLocation'] = "";
        $data['responseTime'] = "0";
        if (isset($data['apacheModule']) && in_array($data['apacheModule'], ['php7', 'php8'])) {
            $data = $this->parsePHPErrorLog($data);
        }
        unset($data['logLevel'], $data['module']);
        return $data;
    }

    public function parsePHPErrorLog($data)
    {
        $data['errorMessage'] = str_replace(PHP_EOL, '\\n', $data['errorMessage']);
        preg_match_all("@^PHP ([a-zA-Z\s]+):\s+(.+)\s+in\s+(.+) on line (\d+)@i", $data['errorMessage'], $m);
        unset($m[0]);
        $replace = array_column($m, "0");
        $replace[] = "";
        $parsed = [];
        if (count($replace) == 5) {
            $replaceWith = self::$mapErrorFields;
            $parsed = array_combine($replaceWith, $replace);
            $data['inLine'] = $parsed['line'];
            $data['inFile'] = $parsed['file'];
            $data['errorType'] = $data['errorType'] . ": PHP " . $parsed['type'];
        } elseif (strpos($data['errorMessage'], "base64") === 0) {
            preg_match_all("@base64:(.+)@i", $data['errorMessage'], $m);
            $msg = json_decode(base64_decode($m[1][0]), true);
            $msg['client'] = $this->getClientFromForwarded($msg['HeaderXForwardedFor']);
            $data = array_merge($data, $msg);
        } else {
            //if other formats should be supported, please write code in this elif block
            throw new Exception("PHP PARSE ERROR " . $data['errorMessage']);
        }
        if (strpos($data['errorMessage'], "Stack trace") !== false) {
            $mm = [];
            preg_match_all("@^(.+) in .+Stack trace:(.+)\s+(thrown)?@i", trim($data['errorMessage']), $mm);
            $trace = array_column($mm, "0");
            $data['errorMessage'] = trim($trace[1]);
            $data['stackTrace'] = implode(PHP_EOL, array_filter(explode("\\n", $trace[2]), 'trim'));
        }
        $m = [];
        if (!empty($data['inFile'])) {
            preg_match("@((\/wp-content)?\/(themes|plugins|uploads)\/([^\/]*))|(\/wp-([a-zA-Z0-9-_.]+)([^\s]+)?)@", $data['inFile'], $m);
        } else {
            preg_match("@((\/wp-content)?\/(themes|plugins|uploads)\/([^\/]*))|(\/wp-([a-zA-Z0-9-_.]+)([^\s]+)?)@", $parsed['message'], $m);
        }
        $data['wpType'] = 'other';
        $data['wpLocation'] = 'unknown';
        if (!empty($m)) {
            if (isset($m[5])) {
                $data['wpType'] = 'core';
                $data['wpLocation'] = $m[5];
            } else {
                $data['wpType'] = $m[3];
                $data['wpLocation'] = $m[4];
            }
        }
        return $data;
    }

    public function parseLineMsg($data)
    {
        $data['inFile'] = $data['context'];
        $data['inLine'] = "0";
        $data['wpType'] = "";
        $data['errorMessage'] = $data['message'];
        $data['stackTrace'] = $data['extra'];
        $data['errorType'] = $data['level_name'];
        $data['wpLocation'] = $data['channel'];
        return $data;
    }

    public function parseLineSys($data)
    {
        $data['time'] = date(self::TIMEFORMAT);
        unset($data['client'], $data['HeaderXForwardedFor']);
        $data['tcp'] = $this->getNetstatFormatted();
        $la = array_map(fn($v): float => (float) trim($v), explode(", ", explode("load average:", shell_exec("uptime"))[1]));
        $data['load'] = array_combine(['01m', '05m', '15m'], $la);
        return $data;
    }

    private function getNetstatFormatted()
    {
        $d = array_map('trim', explode(PHP_EOL, trim(shell_exec("netstat -la | grep tcp | awk -F ' ' {'print $5,$6'} | sort | uniq -c"))));
        $r = [];
        foreach ($d as $k => $v) {
            $tmp = array_combine(['count', 'client', 'type'], explode(" ", $v));
            $tmp['count'] = (int) $tmp['count'];
            $cl = explode(":", $tmp['client']);
            $tmp['client'] = $cl[0];
            $tmp['port'] = $cl[1];
            $d[$k] = $tmp;
            if (!preg_match('/[^a-zA-Z]/', $tmp['port']) && !empty($tmp['port'])) {
                @$r['port'][$tmp['port']] += $tmp['count'];
            }
//            @$r['client'][$tmp['client']] += $tmp['count'];
            @$r['type'][$tmp['type']] += $tmp['count'];
        }
        return $r;
    }

    private function getClientFromForwarded($forwarded)
    {
        $forwardeda = explode(",", $forwarded)[0];
        $client = trim(explode(":", $forwardeda)[0]);
        $clientf = filter_var($client, FILTER_VALIDATE_IP);
        return $clientf ? $clientf : "0.0.0.0";
    }

    public function excludeUA($logline)
    {
        switch ($this->indexType) {
            case self::INDEX:
            case self::WPINDEX:
                return (strpos($logline, 'internal dummy connection') !== false);
            default:
                break;
        }
        return false;
    }
}