<?php

namespace Gtlogistics\Sap\Odata;

use Gtlogistics\Sap\Odata\Exception\UnknownException;
use Gtlogistics\Sap\Odata\Model\SapMetadata;
use Gtlogistics\Sap\Odata\Model\SapEntity;
use Gtlogistics\Sap\Odata\Util\UriUtils;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class SapMetadataProvider
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly UriFactoryInterface $uriFactory,
    ) {
    }

    public function getEntityMetadata(string $entity): SapEntity
    {
        return $this->retrieveMetadata($entity)->getEntity($entity);
    }

    public function getMetadata(): SapMetadata
    {
        return $this->retrieveMetadata();
    }

    private function retrieveMetadata(?string $entity = null): SapMetadata
    {
        $query = [];
        if ($entity !== null) {
            $query['entityset'] = $entity;
        }

        $uri = $this->uriFactory->createUri('/$metadata');
        $request = $this->requestFactory->createRequest('GET', UriUtils::serializeQuery($uri, $query));
        $response = $this->httpClient->sendRequest($request);
        $body = $response->getBody()->__toString();

        if ($response->getStatusCode() !== 200) {
            throw new UnknownException($body ?: 'Unknown error');
        }

        return SapMetadata::fromXml(new \SimpleXMLElement($body));
    }
}
