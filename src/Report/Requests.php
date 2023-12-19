<?php

namespace Logs2ELK\Report;

class Requests extends AbstractReport
{

    public function requests($params = null)
    {

        $from = strtotime("2023-10-02 12:00:00");
        $to = $from + (self::TIME_STEP * 60);
        $gteTime = gmdate("Y-m-d H:i:s O", $from);
        $ltTime = gmdate("Y-m-d H:i:s O", $to);
        $params = self::PARAMS;
        $params['body']['query']['bool']['filter'][2]['range'] = ['time' => ['gte' => $gteTime, 'lt' => $ltTime]];
        $params['body']['aggs']['unique_values']['composite']['size'] = self::RESULTS;
        $result = [];
        while ($this->lastResults > 0) {
            echo "fetching " . $params['body']['aggs']['unique_values']['composite']['size'] . " results"
            . " from " . $params['body']['query']['bool']['filter'][2]['range']['time']['gte']
            . " to " . $params['body']['query']['bool']['filter'][2]['range']['time']['lt']
            . PHP_EOL;

            $list = $this->getPartR($params, $from, $to);

            foreach ($list as $uri => $count) {
                if (isset($result[$uri])) {
                    $result[$uri] = $result[$uri] + $count;
                } else {
                    $result[$uri] = $count;
                }
            }

            $from = $to;
            $to = $to + (self::TIME_STEP * 60);
            $this->updateTimeParams($params, $from, $to);
        }
        arsort($result);
        file_put_contents($this->filename, json_encode($result));
        return $result;
    }

    public function getPartR(&$params, $from, $to)
    {
        $response = $this->client->search($params);
        $this->lastResults = count($response['aggregations']['unique_values']['buckets']);

        if ($this->lastResults == self::RESULTS) {
            echo "more results than limit, splitting time" . PHP_EOL;
            $to = $to - (self::TIME_STEP / 2 * 60);
            $this->updateTimeParams($params, $from, $to);
            return $this->getPartR($params, $from, $to);
        } else {
            echo "fetched $this->lastResults " . PHP_EOL;
        }
        // Przetwarzanie wyników
        $values = [];

        foreach ($response['aggregations']['unique_values']['buckets'] as $bucket) {
            $count = $bucket['doc_count'];
            $unique = preg_replace('/[0-9]+/', '{int}', $bucket['key']['unique_field']);
            if (!str_starts_with($unique, "/api")) {
                continue;
            }
            if (!isset($values[$unique])) {
                $values[$unique] = $count;
            } else {
                $values[$unique] = $values[$unique] + $count;
            }
        }
        return $values;
    }

    function updateTimeParams(&$params, $from, $to)
    {
        $gteTime = gmdate("Y-m-d H:i:s O", $from);
        $ltTime = gmdate("Y-m-d H:i:s O", $to);
        $params['body']['query']['bool']['filter'][2]['range'] = ['time' => ['gte' => $gteTime, 'lt' => $ltTime]];
    }
}
