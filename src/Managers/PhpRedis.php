<?php

/**
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnused
 */

namespace Oilstone\RedisCache\Managers;

/**
 * Class PhpRedis
 * @package Oilstone\RedisCache\Managers
 */
class PhpRedis extends Manager
{
    /**
     * PhpRedis constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        // TODO: Implement get() method.
    }

    /**
     * @inheritDoc
     */
    public function put(string $key, $value, $ttl = null): bool
    {
        // TODO: Implement put() method.
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, $value = 1)
    {
        // TODO: Implement decrement() method.
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, $value = 1)
    {
        // TODO: Implement increment() method.
    }

    /**
     * @inheritDoc
     */
    public function forget(string $key): bool
    {
        // TODO: Implement forget() method.
    }
}