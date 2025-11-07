<?php

namespace Gtlogistics\Sap\Odata;

use Gtlogistics\Sap\Odata\Bridge\Psr18HttpProviderAdapter;
use Gtlogistics\Sap\Odata\Enum\ODataVersion;
use Gtlogistics\Sap\Odata\OData\SapErrorPlugin;
use Gtlogistics\Sap\Odata\OData\ODataV2Plugin;
use Http\Client\Common\PluginClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SaintSystems\OData\IODataClient;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\Query\Builder;

final class SapEntityClient
{
    private readonly string $link;

    public function __construct(
        private readonly IODataClient $odataClient,
        string $link,
    ) {
        $this->link = '/' . trim($link, '/');
    }

    public static function create(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ODataVersion $odataVersion,
        string $link,
    ): self {
        $plugins = [new SapErrorPlugin()];

        // The ODataClient speak OData V4 natively,
        // so we add a translation plugin from V4 to V2
        if ($odataVersion === ODataVersion::VERSION_2) {
            $plugins[] = new ODataV2Plugin($streamFactory);
        }

        return new self(
            new ODataClient(
                // We use a default base URL to comply with the library,
                // but we replace further up in the plugin client chain
                'https://example.com/',
                null,
                new Psr18HttpProviderAdapter(
                    new PluginClient(
                        $httpClient,
                        $plugins,
                    ),
                    $requestFactory,
                    $streamFactory
                )
            ),
            $link,
        );
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function query(): Builder
    {
        return $this->odataClient
            ->from($this->link)
        ;
    }
}
