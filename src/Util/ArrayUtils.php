<?php

namespace Gtlogistics\Sap\Odata\Util;

final class ArrayUtils
{
    /**
     * Static
     */
    private function __construct()
    {
    }

    /**
     * @template TArray of array
     * @template TKey of key-of<TArray>
     *
     * @param TKey $key
     * @param TArray $array
     *
     * @return TArray[TKey]
     */
    public static function popKey(string|int $key, array &$array): mixed
    {
        $value = $array[$key];
        unset($array[$key]);

        return $value;
    }
}
