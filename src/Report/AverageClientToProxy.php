<?php

namespace Logs2ELK\Report;

class AverageClientToProxy extends AbstractReport
{

    public function getPart($requestURI = null)
    {
        $from = strtotime($this->dateFrom);
        $to = $this->dateTo ? strtotime($this->dateTo) : time();

        $gteTime = $this->gmdate($from);
        $ltTime = $this->gmdate($to);

        $params = self::$report_params;
        $params['body']['query']['bool']['filter'][0]['range'] = ['time' => ['gte' => $gteTime, 'lt' => $ltTime]];

        if (str_contains($requestURI, "{int}")) {
            $params['body']['query']['bool']['filter'][]['wildcard']['requestURI'] = str_replace("{int}", "*", $requestURI);
        } else {
            $params['body']['query']['bool']['filter'][]['match_phrase']['requestURI'] = $requestURI;
        }
        $response = $this->search($params);
        return $response['aggregations']['avgtime']['value'];
    }
}
