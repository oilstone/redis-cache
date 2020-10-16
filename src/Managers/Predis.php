<?php

/**
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnused
 * @noinspection PhpMissingFieldTypeInspection
 * @noinspection PhpUndefinedClassInspection
 * @noinspection PhpUndefinedNamespaceInspection
 */

namespace Oilstone\RedisCache\Managers;

use Predis\Client;

/**
 * Class Predis
 * @package Oilstone\RedisCache\Managers
 */
class Predis extends Manager
{
    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var string|null
     */
    protected $scheme = 'tcp';

    /**
     * @var bool
     */
    protected $appendPort = true;

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        $value = $this->client->get($key);

        if ($value === null) {
            return $default;
        }

        return unserialize($value);
    }

    /**
     * @inheritDoc
     */
    public function put(string $key, $value, $ttl = null): bool
    {
        $this->client->set($key, serialize($value));
        $this->client->expire($key, $ttl);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, $value = 1)
    {
        return $this->client->decrby($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, $value = 1)
    {
        return $this->client->incrby($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function forget(string $key): bool
    {
        return $this->client->del($key);
    }

    /**
     * @inheritDoc
     */
    protected function connect(): void
    {
        $options = [];

        if (isset($this->config['options']['prefix'])) {
            $options['prefix'] = $this->config['options']['prefix'] . ':';
        }

        $this->client = new Client($this->resolveConnection($this->config['connections'][0] ?? []), $options);
    }
}