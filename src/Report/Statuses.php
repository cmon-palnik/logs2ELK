<?php

namespace Logs2ELK\Report;

class Statuses extends AbstractReport
{

    public function getPart($requestURI = null)
    {
        $from = strtotime("2023-10-01 13:00:00");
        $to = strtotime('now');
        $gteTime = gmdate("Y-m-d H:i:s O", $from);
        $ltTime = gmdate("Y-m-d H:i:s O", $to);
        $params = self::PARAMS;
        $params['body']['query']['bool']['filter'][0]['range'] = ['time' => ['gte' => $gteTime, 'lt' => $ltTime]];

        if (str_contains($requestURI, "{int}")) {
            $params['body']['query']['bool']['filter'][]['wildcard']['requestURI'] = str_replace("{int}", "*", $requestURI);
        } else {
            $params['body']['query']['bool']['filter'][]['match_phrase']['requestURI'] = $requestURI;
        }
        $response = $this->client->search($params);
        if (empty($response['aggregations']['cnta']['buckets'])) {
            print_r([
                $params['body']['query']['bool']['filter'],
                $requestURI,
                $response['aggregations']['cnta']['buckets']
                ]
            );
        }
        $values = [];
        foreach ($response['aggregations']['cnta']['buckets'] as $bucket) {
            $values[$bucket['key']] = $bucket['doc_count'];
        }
        return $values;
    }
}
