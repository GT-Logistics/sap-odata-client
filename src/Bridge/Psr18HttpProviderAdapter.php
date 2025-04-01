<?php

namespace Gtlogistics\Sap\Odata\Bridge;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SaintSystems\OData\HttpRequestMessage;
use SaintSystems\OData\IHttpProvider;

/**
 * @internal
 */
final class Psr18HttpProviderAdapter implements IHttpProvider
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function send(HttpRequestMessage $request): mixed
    {
        $httpRequest = $this->requestFactory->createRequest((string) $request->method, $request->requestUri);
        $httpRequest = $httpRequest->withBody($this->streamFactory->createStream($request->body ?? ''));

        foreach ($request->headers as $name => $value) {
            $httpRequest = $httpRequest->withHeader($name, $value);
        }

        return $this->httpClient->sendRequest($httpRequest);
    }
}
