<?php

/**
 * @noinspection PhpUnused
 */

namespace Oilstone\RedisCache;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class Cache
 * @package Oilstone\RedisCache
 * @method static bool add(string $key, $value, DateTimeInterface|DateInterval|int $ttl = null) Store an item in the cache if the key does not exist.
 * @method static bool forever(string $key, $value) Store an item in the cache indefinitely.
 * @method static bool forget(string $key) Remove an item from the cache.
 * @method static bool has(string $key) Determine if an item exists in the cache.
 * @method static bool missing(string $key) Determine if an item doesn't exist in the cache.
 * @method static bool put(string $key, $value, DateTimeInterface|DateInterval|int $ttl = null) Store an item in the cache.
 * @method static int|bool decrement(string $key, $value = 1) Decrement the value of an item in the cache.
 * @method static int|bool increment(string $key, $value = 1) Increment the value of an item in the cache.
 * @method static mixed get(string $key, mixed $default = null) Retrieve an item from the cache by key.
 * @method static mixed pull(string $key, mixed $default = null) Retrieve an item from the cache and delete it.
 * @method static mixed remember(string $key, DateTimeInterface|DateInterval|int $ttl, Closure $callback) Get an item from the cache, or execute the given Closure and store the result.
 * @method static mixed rememberForever(string $key, Closure $callback) Get an item from the cache, or execute the given Closure and store the result forever.
 * @method static mixed sear(string $key, Closure $callback) Get an item from the cache, or execute the given Closure and store the result forever.
 */
class Cache
{
    /**
     * @param $name
     * @param $arguments
     * @return mixed|null
     */
    public static function __callStatic($name, $arguments)
    {
        try {
            if ($cacheManager = Container::getInstance()->make('cache')) {
                return $cacheManager->{$name}(...$arguments);
            }
        } catch (BindingResolutionException $e) {
            //
        }

        return null;
    }
}