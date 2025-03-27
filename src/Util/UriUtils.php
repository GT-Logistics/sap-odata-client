<?php

namespace Gtlogistics\Sap\Odata\Util;

use Psr\Http\Message\UriInterface;

final readonly class UriUtils
{
    /**
     * Static
     */
    private function __construct()
    {
    }

    /**
     * @return  array<string, mixed>
     */
    public static function parseQuery(UriInterface $uri): array
    {
        parse_str($uri->getQuery(), $query);

        return $query;
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function serializeQuery(UriInterface $uri, array $query): UriInterface
    {
        return $uri->withQuery(http_build_query($query));
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    public static function replaceQuery(UriInterface $uri, array $queryParams): UriInterface
    {
        $query = self::parseQuery($uri);
        $query = [...$query, ...$queryParams];

        return self::serializeQuery($uri, $query);
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    public static function appendQuery(UriInterface $uri, array $queryParams): UriInterface
    {
        $query = self::parseQuery($uri);
        $query += $queryParams;

        return self::serializeQuery($uri, $query);
    }
}
