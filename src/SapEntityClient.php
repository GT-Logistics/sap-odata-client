<?php

namespace Gtlogistics\Sap\Odata;

use Gtlogistics\Sap\Odata\Bridge\Psr18HttpProviderAdapter;
use Gtlogistics\Sap\Odata\Model\SapEntity;
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
        string $link,
    ): self {
        return new self(
            new ODataClient(
                '/',
                null,
                new Psr18HttpProviderAdapter(
                    new PluginClient(
                        $httpClient,
                        [
                            new ODataV2Plugin($streamFactory),
                        ],
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

    public function getName(): ?string
    {
        return $this->getMetadata()->getName();
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
