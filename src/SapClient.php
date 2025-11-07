<?php

namespace Gtlogistics\Sap\Odata;

use Gtlogistics\Sap\Odata\Enum\ODataVersion;
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
        private readonly ODataVersion $odataVersion,
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

    public static function forHana(
        string $hostname,
        string $username,
        string $password,
        ?ClientInterface $httpClient = null,
        ?UriFactoryInterface $uriFactory = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ODataVersion $odataVersion = ODataVersion::VERSION_2,
    ): self {
        $endpoint = match ($odataVersion) {
            ODataVersion::VERSION_2 => 'sap/opu/odata/sap/',
            ODataVersion::VERSION_4 => 'sap/opu/odata4/sap/',
        };

        return self::create(
            $hostname,
            $endpoint,
            $username,
            $password,
            $httpClient,
            $uriFactory,
            $requestFactory,
            $streamFactory,
            $odataVersion,
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
        ODataVersion $odataVersion = ODataVersion::VERSION_2,
    ): self {
        $httpClient ??= Psr18ClientDiscovery::find();
        $uriFactory ??= Psr17FactoryDiscovery::findUriFactory();
        $requestFactory ??= Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory ??= Psr17FactoryDiscovery::findStreamFactory();

        return new self(
            new PluginClient(
                $httpClient,
                [
                    new BaseUriPlugin(
                        $uriFactory->createUri(rtrim($hostname, '/') . '/' . $endpoint),
                        // Needed to keep the host in the OData client
                        ['replace' => true],
                    ),
                    new AuthenticationPlugin(new BasicAuth($username, $password)),
                ],
            ),
            $uriFactory,
            $requestFactory,
            $streamFactory,
            $odataVersion,
        );
    }

    public function getMetadataProvider(): SapMetadataProvider
    {
        return new SapMetadataProvider(
            $this->httpClient,
            $this->requestFactory,
            $this->uriFactory,
        );
    }

    public function getEntity(string $link): SapEntityClient
    {
        return SapEntityClient::create($this->httpClient, $this->requestFactory, $this->streamFactory, $this->odataVersion, $link);
    }
}
