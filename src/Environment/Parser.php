<?php

namespace Logs2ELK\Environment;

use Logs2ELK\ConfigLoader;
use Logs2ELK\Environment\Type\Index as IndexType;
use Logs2ELK\ExceptionCode as Code;
use Logs2ELK\ParserException;

class Parser extends Environment
{
    public static array $mapErrorFields = [
        'type', 'message', 'file', 'line', 'trace'
    ];

    public function parseLineByType(array $data = []): array
    {
        if (isset($data['datetime'])) {
            $data['time'] = $data['datetime'];
            $data['HeaderXForwardedFor'] = "0.0.0.0:0";
        }
        $data['application'] = $this->application;
        $data['env'] = $this->env->value;
        $data['node'] = $this->host;
        $data['time'] = date(ConfigLoader::getTimeFormat());
        $data['client'] = $this->getClientFromForwarded($data['HeaderXForwardedFor']);

        switch ($this->indexType) {
            case IndexType::INDEX:
            case IndexType::WPINDEX:
                if (isset($data['logLevel'])) {
                    throw ParserException::withCode(Code::NOT_TRAFFIC_LOG);
                }
                return $this->parseLineTraffic($data);
            case IndexType::WPERROR:
            case IndexType::ERROR:
                if (!isset($data['logLevel'])) {
                    throw ParserException::withCode(Code::NOT_ERROR_LOG);
                }
                return $this->parseLineError($data);
            case IndexType::APPSYS:
                return $this->parseLineSys($data);
            default:
                return [];
        }
    }

    public function parseLineTraffic($data): array
    {
        $data['deviceType'] = $this->browser->getOS($data['userAgent'])['os_type'];
        return $data;
    }

    public function parseLineError(array $data): array
    {
        $data['errorType'] = $data['logLevel'];
        $data['inFile'] = "";
        $data['inLine'] = "0";
        $data['stackTrace'] = "";
        $data['wpType'] = "";
        $data['wpLocation'] = "";
        $data['responseTime'] = "0";
        if (str_starts_with($data['apacheModule'] ?? '', 'php')) {
            $data = $this->parsePHPErrorLog($data);
        }
        unset($data['logLevel'], $data['module']);
        return $data;
    }

    public function parsePHPErrorLog(array $data): array
    {
        $data['errorMessage'] = str_replace(PHP_EOL, '\\n', $data['errorMessage']);
        preg_match_all("@^PHP ([a-zA-Z\s]+):\s+(.+)\s+in\s+(.+)(?: on line |:)(\d+)@i", $data['errorMessage'], $m);
        $this->logger->warning('parsePHPerrorLog debug: ' . serialize($m));
        unset($m[0]);
        $replace = array_column($m, "0");
        $replace[] = "";
        $parsed = [];
        $this->logger->warning('parse PHP error cd.: ' .serialize($replace));
        if (count($replace) == 5) {
            $replaceWith = self::$mapErrorFields;
            $parsed = array_combine($replaceWith, $replace);
            $data['inLine'] = $parsed['line'];
            $data['inFile'] = $parsed['file'];
            $data['errorType'] = $data['errorType'] . ": PHP " . $parsed['type'];

        } elseif (str_starts_with($data['errorMessage'], "base64")) {
            preg_match_all("@base64:(.+)@i", $data['errorMessage'], $m);
            $msg = json_decode(base64_decode($m[1][0]), true);
            $msg['client'] = $this->getClientFromForwarded($msg['HeaderXForwardedFor']);
            $data = array_merge($data, $msg);

        } else {
            //if other formats should be supported, please write code in this elif block
            throw ParserException::withCode(Code::PHP_PARSE_ERROR, ['message' => $data['errorMessage']]);
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

    public function parseLineSys(array $data = []): array
    {
        $data['time'] = date(ConfigLoader::getTimeFormat());
        unset($data['client'], $data['HeaderXForwardedFor']);
        $data['tcp'] = $this->getNetstatFormatted();
        $la = array_map(fn($v): float => (float) trim($v), explode(", ", explode("load average:", shell_exec("uptime"))[1]));
        $data['load'] = array_combine(['01m', '05m', '15m'], $la);
        return $data;
    }

    private function getNetstatFormatted(): array
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

    private function getClientFromForwarded(?string $forwarded): string
    {
        $forwardeda = explode(",", $forwarded)[0];
        $client = trim(explode(":", $forwardeda ?? '')[0]);
        $clientf = filter_var($client, FILTER_VALIDATE_IP);
        return $clientf ?: "0.0.0.0";
    }

    public function excludeUserAgent(string $logline): bool
    {
        switch ($this->indexType) {
            case IndexType::INDEX:
            case IndexType::WPINDEX:
                return str_contains($logline, 'internal dummy connection');
            default:
                break;
        }
        return false;
    }
}
