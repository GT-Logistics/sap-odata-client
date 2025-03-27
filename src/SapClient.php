<?php

namespace Gtlogistics\Sap\Odata;

use Gtlogistics\Sap\Odata\Exception\UnknownException;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Message\Authentication\BasicAuth;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class SapClient
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly UriFactoryInterface $uriFactory,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public static function forReports(
        string $hostname,
        string $username,
        string $password,
        ?ClientInterface $httpClient = null,
        ?UriFactoryInterface $uriFactory = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ): self {
        return self::create(
            $hostname,
            'sap/byd/odata/',
            $username,
            $password,
            $httpClient,
            $uriFactory,
            $requestFactory,
            $streamFactory,
        );
    }

    public static function forAnalytics(
        string $hostname,
        string $username,
        string $password,
        ?ClientInterface $httpClient = null,
        ?UriFactoryInterface $uriFactory = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ): self {
        return self::create(
            $hostname,
            'sap/byd/odata/analytics/ds/',
            $username,
            $password,
            $httpClient,
            $uriFactory,
            $requestFactory,
            $streamFactory,
        );
    }

    public static function create(
        string $hostname,
        string $endpoint,
        string $username,
        string $password,
        ?ClientInterface $httpClient = null,
        ?UriFactoryInterface $uriFactory = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ): self {
        $httpClient ??= Psr18ClientDiscovery::find();
        $uriFactory ??= Psr17FactoryDiscovery::findUriFactory();
        $requestFactory ??= Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory ??= Psr17FactoryDiscovery::findStreamFactory();

        return new self(
            new PluginClient(
                $httpClient,
                [
                    new BaseUriPlugin($uriFactory->createUri(rtrim($hostname, '/') . '/' . $endpoint)),
                    new AuthenticationPlugin(new BasicAuth($username, $password)),
                ],
            ),
            $uriFactory,
            $requestFactory,
            $streamFactory,
        );
    }

    /**
     * @return iterable<SapServiceClient>
     */
    public function getServices(): iterable
    {
        $request = $this->requestFactory->createRequest('GET', '');
        $response = $this->httpClient->sendRequest($request);
        $body = $response->getBody()->__toString();

        if ($response->getStatusCode() !== 200) {
            throw new UnknownException($body ?: 'Unknown error');
        }

        $feed = new \SimpleXMLElement($body);
        foreach ($feed->xpath('//atom:link') as $link) {
            yield $this->getService($link['href']);
        }
    }

    public function getService(string $link): SapServiceClient
    {
        return SapServiceClient::create($this->httpClient, $this->uriFactory, $this->requestFactory, $this->streamFactory, $link);
    }
}
