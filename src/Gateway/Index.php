<?php

namespace Logs2ELK\Gateway;

use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch;
use GuzzleHttp\Promise\Promise;
use Logs2ELK\ExceptionCode as Code;

class Index extends AbstractGateway
{

    public function create(array $params): bool
    {
        $response = $this->indices()
            ->create($params);

        $this->exceptionWhenBadResponse($response, Code::CANNOT_CREATE_INDEX, $params);
        return true;
    }

    public function delete(string $index): bool
    {
        $params = ['index' => $index];
        $response = $this->indices()
            ->delete($params);

        $this->exceptionWhenBadResponse($response, Code::CANNOT_DELETE_INDEX, $params);
        return true;
    }
    public function exists(string $index): bool
    {
        return $this->indices()
            ->exists(['index' => $index])
            ->asBool();
    }

    public function getIndexesByName($name): array
    {
        $params = ['index' => $name];
        $response = $this->get($params);

        $this->exceptionWhenBadResponse($response, Code::CANNOT_GET_INDEXES, $params);
        if(empty($response->asString())) {
            return [];
        }
        return $response->asArray();
    }
    public function get($params): Elasticsearch|Promise
    {
        return $this->client->cat()->indices($params);
    }

    public function getMapping(string $index): array
    {
        $params = ['index' => $index];
        $response = $this->indices()
            ->getMapping($params);

        $this->exceptionWhenBadResponse($response, Code::CANNOT_GET_MAPPING, $params);
        if (empty($response->asString())) {
            return [];
        }
        return $response->asArray();
    }
    public function put($index, $body): void
    {
        $params = ['body' => $body, 'index' => $index];
        $response = $this->client
            ->index($params);

        $this->exceptionWhenBadResponse($response, Code::CANNOT_INDEX_DATA, $params);
        return;
    }

    public function indices(): Indices {
        return $this->client->indices();
    }
}
