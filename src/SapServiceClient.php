<?php

namespace Gtlogistics\Sap\Odata;

use Gtlogistics\Sap\Odata\Exception\UnknownException;
use Http\Client\Common\Plugin\AddPathPlugin;
use Http\Client\Common\PluginClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class SapServiceClient
{
    private SapMetadataProvider $metadataProvider;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $link,
    ) {
        $this->metadataProvider = new SapMetadataProvider($httpClient, $requestFactory);
    }

    public static function create(
        ClientInterface $httpClient,
        UriFactoryInterface $uriFactory,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        string $link,
    ): self {
        return new self(
            new PluginClient(
                $httpClient,
                [
                    new AddPathPlugin($uriFactory->createUri('/' . trim($link, '/'))),
                ],
            ),
            $requestFactory,
            $streamFactory,
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
        return SapEntityClient::create($this->httpClient, $this->requestFactory, $this->streamFactory, $this->metadataProvider, $link);
    }
}
