<?php

namespace Logs2ELK\Gateway;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Logs2ELK\ElkException;

class AbstractGateway
{
    public function __construct(protected Client $client)
    {
    }

    public function exceptionWhenBadResponse(
        Elasticsearch $response,
        string $exceptionCode,
        mixed $context = []
    ): void
    {
        if (!$response->asBool()) {
            throw ElkException::withCode($exceptionCode, $context);
        }
    }
}
