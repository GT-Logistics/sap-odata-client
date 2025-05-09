<?php

namespace Gtlogistics\Sap\Odata\OData;

use Gtlogistics\Sap\Odata\Util\ArrayUtils;
use Gtlogistics\Sap\Odata\Util\UriUtils;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use SaintSystems\OData\Constants;
use SaintSystems\OData\RequestHeader;

/**
 * OData middleware from 2.0 to 4.0
 *
 * @internal
 */
final class ODataV2Plugin implements Plugin
{
    public function __construct(
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $preferences = $this->parsePreferences($request);

        $request = $request
            ->withUri(UriUtils::replaceQuery($request->getUri(), [
                '$top' => $preferences[Constants::ODATA_MAX_PAGE_SIZE][0] ?? Constants::ODATA_MAX_PAGE_SIZE_DEFAULT,
                '$inlinecount' => 'allpages',
            ]))
            ->withHeader(RequestHeader::ODATA_VERSION, '2.0')
            ->withHeader(RequestHeader::ODATA_MAX_VERSION, '2.0')
            ->withoutHeader(RequestHeader::PREFER)
        ;

        return $next($request)->then(function (ResponseInterface $response) use ($request): ResponseInterface {
            if ($response->getStatusCode() !== 200) {
                return $response;
            }

            $odataV2 = json_decode($response->getBody()->__toString(), true, flags: JSON_THROW_ON_ERROR);
            $odataV4 = [Constants::ODATA_VALUE => array_map($this->parseEntry(...), $odataV2['d']['results'])];

            if ($nextLink = $this->generateNextLink($request->getUri(), $odataV2['d']['__count'])) {
                $odataV4[Constants::ODATA_NEXT_LINK] = $nextLink->__toString();
            }

            return $response
                ->withBody($this->streamFactory->createStream(json_encode($odataV4, JSON_THROW_ON_ERROR)))
            ;
        });
    }

    private function generateNextLink(UriInterface $originalUri, int $count): ?UriInterface
    {
        $params = UriUtils::parseQuery($originalUri);
        $pageSize = (int) $params['$top'];
        $current = (int) ($params['$skip'] ?? 0);
        $next = $current + $pageSize;

        if ($next >= $count) {
            return null;
        }

        return UriUtils::replaceQuery($originalUri, [
            '$skip' => $next,
        ]);
    }

    /**
     * @return array<string, string[]>
     */
    private function parsePreferences(RequestInterface $request): array
    {
        $preferences = [];
        foreach ($request->getHeader('prefer') as $header) {
            $rawPreferences = \iter\map(
                static fn (string $rawPreference) => explode('=', $rawPreference),
                explode(';', $header),
            );

            foreach ($rawPreferences as [$key, $value]) {
                $preferences[$key][] = $value;
            }
        }

        return $preferences;
    }

    /**
     * @param array<array-key, mixed> $entry
     *
     * @return array<array-key, mixed>
     */
    private function parseEntry(array $entry): array
    {
        $metadata = ArrayUtils::popKey('__metadata', $entry);

        return $entry;
    }
}
