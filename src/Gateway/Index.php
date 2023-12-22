<?php

namespace Logs2ELK\Gateway;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch;
use GuzzleHttp\Promise\Promise;
use Logs2ELK\GeneralException;
use Logs2ELK\GeneralExceptionCode as Code;

class Index
{

    public function __construct(protected Client $client)
    {

    }

    public function create(array $params): bool
    {
        $response = $this->indices()
            ->create($params);
        if (!$response->asBool()) {
            throw GeneralException::withCode(Code::CANNOT_CREATE_INDEX, $params);
        }
        return true;
    }

    public function delete(string $index): bool
    {
        $params = ['index' => $index];
        $response = $this->indices()
            ->delete($params);
        if (!$response->asBool()) {
            throw GeneralException::withCode(Code::CANNOT_DELETE_INDEX, $params);
        }
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
        if (!$response->asBool()) {
            throw GeneralException::withCode(Code::CANNOT_GET_INDEXES, $params);
        }
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
        return $this->indices()
            ->getMapping(['index' => $index])
            ->asArray();
    }
    public function put($index, $body)
    {
        $params = ['body' => $body, 'index' => $index];
        $response = $this->client
            ->index($params);
        if (!$response->asBool()) {
            throw GeneralException::withCode(Code::CANNOT_CREATE_INDEX, $params);
        }
        return true;
    }

    public function indices(): Indices {
        return $this->client->indices();
    }
}
