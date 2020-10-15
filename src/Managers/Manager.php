<?php

/**
 * @noinspection PhpUnused
 */

namespace Oilstone\RedisCache\Managers;

use Closure;
use DateInterval;
use DateTimeInterface;

/**
 * Interface Manager
 * @package Oilstone\RedisCache\Contracts
 */
abstract class Manager
{
    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param string $key
     * @param mixed $value
     * @param DateTimeInterface|DateInterval|int|null $ttl
     * @return bool
     */
    public function add(string $key, $value, $ttl = null): bool
    {
        if (is_null($this->get($key))) {
            return $this->put($key, $value, $ttl);
        }

        return false;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    abstract public function get(string $key, $default = null);

    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param DateTimeInterface|DateInterval|int|null $ttl
     * @return bool
     */
    abstract public function put(string $key, $value, $ttl = null): bool;

    /**
     * Determine if an item doesn't exist in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return !is_null($this->get($key));
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    abstract public function decrement(string $key, $value = 1);

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    abstract public function increment(string $key, $value = 1);

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function pull(string $key, $default = null)
    {
        $value = $this->get($key);

        if (isset($value)) {
            $this->forget($key);
        }

        return $value ?? $default;
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    abstract public function forget(string $key): bool;

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param string $key
     * @param DateTimeInterface|DateInterval|int $ttl
     * @param Closure $callback
     * @return mixed
     */
    public function remember(string $key, $ttl, Closure $callback)
    {
        $value = $this->get($key);

        if (!is_null($value)) {
            return $value;
        }

        $this->put($key, $value = $callback(), $ttl);

        return $value;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param string $key
     * @param Closure $callback
     * @return mixed
     */
    public function sear(string $key, Closure $callback)
    {
        return $this->rememberForever($key, $callback);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param string $key
     * @param Closure $callback
     * @return mixed
     */
    public function rememberForever(string $key, Closure $callback)
    {
        $value = $this->get($key);

        if (!is_null($value)) {
            return $value;
        }

        $this->forever($key, $value = $callback());

        return $value;
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever(string $key, $value): bool
    {
        return $this->put($key, $value);
    }
}