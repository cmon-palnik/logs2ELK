#!/usr/bin/php
<?php

chdir(__DIR__);

date_default_timezone_set('Europe/Warsaw');

require 'vendor/autoload.php';

use Elasticsearch\ClientBuilder;

include_once 'envDefinition.php';

try {
    $ed = new envDefinition();

    $client = ClientBuilder::create()->setHosts(["elasticsearch:80"])->build();

    $index = $ed->getIndexName();

    if (!$client->indices()->exists(['index' => $index])) {
        $indexParams = $ed->getIndexParams($index);
        $client->indices()->create($indexParams);
    }
    try {
        $params = ['body' => $ed->parseLineByType($data), 'index' => $index];
        $response = $client->index($params);
    } catch (Exception $ex) {
        echo $ex->getMessage() . PHP_EOL;
    }
} catch (Exception $ex) {
    $msg = date("Y-m-d H:i:s") . " GLOBAL EXCEPTION " . PHP_EOL;
    $msg .= $ex->getMessage() . PHP_EOL;
    $msg .= $ex->getTraceAsString() . PHP_EOL;
    echo $ex;
}

