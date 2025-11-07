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

    /**
     * @return iterable<string>
     */
    public function getServices(): iterable
    {
        $request = $this->requestFactory->createRequest('GET', '/');
        $response = $this->httpClient->sendRequest($request);
        $body = $response->getBody()->__toString();

        if ($response->getStatusCode() !== 200) {
            throw new UnknownException($body ?: 'Unknown error');
        }

        $feed = new \SimpleXMLElement($body);
        foreach ($feed->xpath('//atom:link') as $link) {
            yield $link['href'];
        }
    }

    /**
     * @return iterable<string>
     */
    public function getEntities(string $service): iterable
    {
        $request = $this->requestFactory->createRequest('GET', '/' . $service);
        $response = $this->httpClient->sendRequest($request);
        $body = $response->getBody()->__toString();

        if ($response->getStatusCode() !== 200) {
            throw new UnknownException($body ?: 'Unknown error');
        }

        $serviceElement = new \SimpleXMLElement($body);
        $serviceElement->registerXPathNamespace('app', 'http://www.w3.org/2007/app');

        foreach ($serviceElement->xpath('//app:collection') as $collection) {
            yield $collection['href'];
        }
    }

    public function getServiceMetadata(string $service): SapMetadata
    {
        return $this->retrieveMetadata($service);
    }

    public function getEntityMetadata(string $service, string $entity): SapEntity
    {
        return $this->retrieveMetadata($service, $entity)->getEntity($entity);
    }

    private function retrieveMetadata(string $service, ?string $entity = null): SapMetadata
    {
        $query = [];
        if ($entity !== null) {
            $query['entityset'] = $entity;
        }

        $uri = $this->uriFactory->createUri('/' . $service . '/$metadata');
        $request = $this->requestFactory->createRequest('GET', UriUtils::serializeQuery($uri, $query));
        $response = $this->httpClient->sendRequest($request);
        $body = $response->getBody()->__toString();

        if ($response->getStatusCode() !== 200) {
            throw new UnknownException($body ?: 'Unknown error');
        }

        return SapMetadata::fromXml(new \SimpleXMLElement($body));
    }
}
