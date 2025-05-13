<?php

namespace Gtlogistics\Sap\Odata;

use Gtlogistics\Sap\Odata\Enum\ODataVersion;
use Gtlogistics\Sap\Odata\Exception\UnknownException;
use Gtlogistics\Sap\Odata\Model\SapMetadata;
use Http\Client\Common\Plugin\AddPathPlugin;
use Http\Client\Common\PluginClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class SapServiceClient
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly SapMetadataProvider $metadataProvider,
        private readonly ODataVersion $odataVersion,
        private readonly string $link,
    ) {
    }

    public static function create(
        ClientInterface $httpClient,
        UriFactoryInterface $uriFactory,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ODataVersion $odataVersion,
        string $link,
    ): self {
        $scopedClient = new PluginClient(
            $httpClient,
            [
                new AddPathPlugin($uriFactory->createUri('/' . trim($link, '/'))),
            ],
        );

        return new self(
            $scopedClient,
            $requestFactory,
            $streamFactory,
            new SapMetadataProvider(
                $scopedClient,
                $requestFactory,
                $uriFactory,
            ),
            $odataVersion,
            $link,
        );
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getEntities(): iterable
    {
        $request = $this->requestFactory->createRequest('GET', '/');
        $response = $this->httpClient->sendRequest($request);
        $body = $response->getBody()->__toString();

        if ($response->getStatusCode() !== 200) {
            throw new UnknownException($body ?: 'Unknown error');
        }

        $service = new \SimpleXMLElement($body);
        $service->registerXPathNamespace('app', 'http://www.w3.org/2007/app');

        foreach ($service->xpath('//app:collection') as $collection) {
            yield $this->getEntity($collection['href']);
        }
    }

    public function getEntity(string $link): SapEntityClient
    {
        return SapEntityClient::create($this->httpClient, $this->requestFactory, $this->streamFactory, $this->metadataProvider, $this->odataVersion, $link);
    }

    public function getMetadata(): SapMetadata
    {
        return $this->metadataProvider->getMetadata();
    }
}
