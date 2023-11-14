#!/usr/bin/php
<?php

chdir(__DIR__);

require 'vendor/autoload.php';

use Elasticsearch\ClientBuilder;

include_once 'envDefinition.php';

class indexManager {

    private $dates = [];
    private $allIndexesBaseParams = [];
    private $removeIndexes = [];
    private $checkIndexes = [];

    public function __construct() {
        $client = ClientBuilder::create()->setHosts(["elasticsearch:80"])->build();
        $this->getIndexes($client);
        $this->markIndexesToRemove();
        $this->checkIndexes($client);
        $this->removeOldIndexes($client);
    }

    private function getIndexes($client) {
        $ed = new envDefinition();
        foreach ($ed->indexes as $index) {
            $indexPrefix = $ed->buildIndexPrefix($index) . "*";
            $indexes = $client->cat()->indices(array('index' => $indexPrefix));
            if (!empty($indexes)) {
                $this->sortIndexes($indexes);
                echo "found " . count($indexes) . " idexes for pattern:" . $indexPrefix . PHP_EOL;
            } else {
                echo "no indexes for pattern:" . $indexPrefix . PHP_EOL;
            }
        }
    }

    private function sortIndexes($indexes) {
        foreach ($indexes as $index) {
            $indexParams = explode("-", $index['index']);
            $this->dates[end($indexParams)][$index['index']] = $index;
            $this->allIndexesBaseParams[$index['index']]['baseParams'] = $indexParams;
        }
    }

    private function markIndexesToRemove() {
        $allowedDates = [
            date("Y.W"),
            date("Y.W", strtotime("-1 week")),
            date("Y.W", strtotime("-2 week")),
        ];
        
        foreach($this->dates as $date=>$indexes){
            if(!in_array($date, $allowedDates)){
                $this->removeIndexes = array_merge($this->removeIndexes, array_keys($indexes));
            }
            else{
                $this->checkIndexes = array_merge($this->checkIndexes, array_keys($indexes));
            }
        }
    }

    public function removeOldIndexes(Elasticsearch\Client $client) {
        foreach($this->removeIndexes as $index){
            echo "deleting index $index".PHP_EOL;
            $client->indices()->delete(['index'=>$index]);
        }
    }

    public function checkIndexes(Elasticsearch\Client $client) {
        foreach($this->checkIndexes as $index){
            echo "checking index $index".PHP_EOL;
            $indexBaseParams = $this->allIndexesBaseParams[$index]['baseParams'];
            $configMapping = json_decode(file_get_contents(__DIR__ . "/config/mapping-" . $indexBaseParams[0] . ".json"), true);
            $mapping = $client->indices()->getMapping(['index'=>$index]);
            $nm = $mapping[$index]['mappings']['properties']['time'];
            $sm = $configMapping['properties']['time'];
            $diff = array_diff_assoc($sm, $nm);
            if(!empty($diff)){
                $this->removeIndexes[] = $index;
            }
        }
    }
}

try {
    new indexManager();
} catch (Exception $ex) {
    $msg = date("Y-m-d H:i:s") . " GLOBAL EXCEPTION " . PHP_EOL;
    $msg .= $ex->getMessage() . PHP_EOL;
    $msg .= $ex->getTraceAsString() . PHP_EOL;
    echo $ex;
}

