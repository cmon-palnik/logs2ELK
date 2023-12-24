<?php

namespace Logs2ELK\Gateway;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Logs2ELK\ElkException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractGateway
{
    public function __construct(
        protected Client $client,
        protected OptionsResolver $optionsResolver
    )
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
