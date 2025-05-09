<?php

namespace Gtlogistics\Sap\Odata\Util;

/**
 * @internal
 */
final class TypeUtils
{
    // Static class
    private function __construct()
    {
    }

    public static function parseBoolean(string $value, bool $default = false): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
