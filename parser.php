#!/usr/bin/php
<?php

require 'vendor/autoload.php';
date_default_timezone_set('Europe/Warsaw');
use Elasticsearch\ClientBuilder;

include_once 'envDefinition.php';

try {
    $ed = new envDefinition();
    $index = $ed->getIndexName();

    try {
        $client = ClientBuilder::create()->setHosts(["elasticsearch:80"])->build();

        if (!$client->indices()->exists(['index' => $index])) {
            $indexParams = $ed->getIndexParams($index);
            $client->indices()->create($indexParams);
        }
    } catch (Exception $ex) {
        echo "Elastic server issue, please check host, or index creation" . PHP_EOL;
        echo $ex->getMessage() . PHP_EOL;
    }

    while ($line = fgets(STDIN)) {
        if ($ed->excludeUA($line)) {
            echo "EXLUDED LOG:" . $line . PHP_EOL;
            continue;
        }
        $data = json_decode($line, true);
        if (!$data) {
            continue;
        }
        $data['message'] = $line;
        try {
            $params = ['body' => $ed->parseLineByType($data), 'index' => $index];
            $response = $client->index($params);
        } catch (Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            continue;
        }
    }
} catch (Exception $ex) {
    $msg = date("Y-m-d H:i:s") . " GLOBAL EXCEPTION " . PHP_EOL;
    $msg .= $ex->getMessage() . PHP_EOL;
    $msg .= $ex->getTraceAsString() . PHP_EOL;
    echo $ex;
}

