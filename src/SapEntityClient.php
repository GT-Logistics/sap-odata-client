<?php

namespace Gtlogistics\Sap\Odata;

use Gtlogistics\Sap\Odata\Bridge\Psr18HttpProviderAdapter;
use Gtlogistics\Sap\Odata\Enum\ODataVersion;
use Gtlogistics\Sap\Odata\Model\SapEntity;
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
    public function __construct(
        private readonly IODataClient $odataClient,
        private readonly SapMetadataProvider $metadataProvider,
        private readonly string $link,
    ) {
    }

    public static function create(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        SapMetadataProvider $metadataProvider,
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
                '/',
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
            $metadataProvider,
            $link,
        );
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getMetadata(): SapEntity
    {
        return $this->metadataProvider->getEntityMetadata($this->link);
    }

    public function query(): Builder
    {
        return $this->odataClient
            ->from($this->link)
        ;
    }
}
