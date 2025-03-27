<?php

namespace Gtlogistics\Sap\Odata;

use Gtlogistics\Sap\Odata\Exception\UnknownException;
use Gtlogistics\Sap\Odata\Model\SapMetadata;
use Gtlogistics\Sap\Odata\Model\SapEntity;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class SapMetadataProvider
{
    private SapMetadata $metadata;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
    ) {
    }

    public function getEntityMetadata(string $entity): SapEntity
    {
        return $this->getMetadata()->getEntity($entity);
    }

    private function getMetadata(): SapMetadata
    {
        return $this->metadata ??= $this->retrieveMetadata();
    }

    private function retrieveMetadata(): SapMetadata
    {
        $request = $this->requestFactory->createRequest('GET', '/$metadata');
        $response = $this->httpClient->sendRequest($request);
        $body = $response->getBody()->__toString();

        if ($response->getStatusCode() !== 200) {
            throw new UnknownException($body ?: 'Unknown error');
        }

        return SapMetadata::fromXml(new \SimpleXMLElement($body));
    }
}
