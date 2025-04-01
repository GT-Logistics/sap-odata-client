<?php

namespace Gtlogistics\Sap\Odata\OData;

use Gtlogistics\Sap\Odata\Exception\SapException;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
class SapErrorPlugin implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next($request)->then(function (ResponseInterface $response): ResponseInterface {
            $statusCode = $response->getStatusCode();

            // Only process 4xx and 5xx errors
            if ($statusCode < 400 || $statusCode > 599) {
                return $response;
            }

            try {
                $data = json_decode($response->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
            }

            throw new SapException(
                $data['error']['message']['value'] ?? 'Unknown error',
                $data['error']['code'] ?? '0',
            );
        });
    }
}
