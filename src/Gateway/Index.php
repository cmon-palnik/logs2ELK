<?php

namespace Logs2ELK\Gateway;

use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch;
use GuzzleHttp\Promise\Promise;
use Logs2ELK\ElkException;
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
        $params = ['index' => $name, 'format' => 'json'];
        $response = $this->get($params);

        $this->exceptionWhenBadResponse($response, Code::CANNOT_GET_INDEXES, $params);
        if(empty($response->asString())) {
            return [];
        }
        try {
            return $response->asArray();
        } catch (\Exception $ex) {
            throw ElkException::withCode(
                Code::CANNOT_GET_INDEXES,
                array_merge($params, [
                    'reason' => 'No JSON in response'
                ])
            );
        }
    }

    public function get($params): Elasticsearch|Promise
    {
        $this->optionsResolver->clear()->setDefaults([
            'index' => '',
            'format' => 'json',
        ]);

        $response = $this->client->cat()->indices(
            $this->optionsResolver->resolve($params)
        );

        if ($response instanceof Promise) {
            return $response->then(fn($result) => $result);
        }
        return $response;
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
