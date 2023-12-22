<?php

namespace Logs2ELK\Gateway;

use Elastic\Elasticsearch\Client;
use Logs2ELK\GeneralException;
use Logs2ELK\GeneralExceptionCode as Code;

class Search
{
    public function __construct(
        protected Client $client
    )
    {
    }

    public function search($params): array
    {
        $response = $this->client->search($params);
        if (!$response->asBool()) {
            throw GeneralException::withCode(Code::CANNOT_SEARCH, $params);
        }
        if(empty($response->asString())) {
            return [];
        }
        return $response->asArray();
    }
}
