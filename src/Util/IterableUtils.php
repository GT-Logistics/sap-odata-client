<?php

namespace Gtlogistics\Sap\Odata\Util;

/**
 * @internal
 */
final class IterableUtils
{
    /**
     * Static
     */
    private function __construct()
    {
    }

    /**
     * @template TValue
     * @template TReturn
     *
     * @param callable(TValue): TReturn $callable
     * @param iterable<TValue> $iterable
     * @return iterable<TReturn>
     */
    public static function map(callable $callable, iterable $iterable): iterable
    {
        foreach ($iterable as $item) {
            yield $callable($item);
        }
    }

    /**
     * @template TValue
     * @template TKey
     *
     * @param callable(TValue): array{TKey, TValue} $callable
     * @param iterable<TValue> $iterable
     * @return iterable<TKey, TValue>
     */
    public static function mapWithKeys(callable $callable, iterable $iterable): iterable
    {
        foreach ($iterable as $item) {
            [$key, $value] = $callable($item);

            yield $key => $value;
        }
    }
}
