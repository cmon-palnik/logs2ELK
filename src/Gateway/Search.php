<?php

namespace Logs2ELK\Gateway;

use Logs2ELK\Exception;
use Logs2ELK\ExceptionCode as Code;

class Search extends AbstractGateway
{

    public function search($params): array
    {
        $response = $this->client->search($params);

        $this->exceptionWhenBadResponse($response, Code::CANNOT_SEARCH, $params);
        if(empty($response->asString())) {
            return [];
        }
        return $response->asArray();
    }
}
