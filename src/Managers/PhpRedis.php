<?php

/**
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnused
 * @noinspection PhpMissingFieldTypeInspection
 */

namespace Oilstone\RedisCache\Managers;

use Redis;

/**
 * Class PhpRedis
 * @package Oilstone\RedisCache\Managers
 */
class PhpRedis extends Manager
{
    /**
     * @var Redis
     */
    protected $client;

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        $value = $this->client->get($key);

        if ($value === false) {
            return $default;
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function put(string $key, $value, $ttl = null): bool
    {
        return $this->client->set($key, $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, $value = 1)
    {
        return $this->client->decrBy($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, $value = 1)
    {
        return $this->client->incrBy($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function forget(string $key): bool
    {
        return $this->client->del($key);
    }

    /**
     * @return void
     */
    protected function connect(): void
    {
        $this->client = new Redis();

        $this->client->connect(
            $this->resolveConnection($this->config ?? []),
            $this->config['port'] ?? 6379,
            $this->config['options']['timeout'] ?? 0,
            $this->config['options']['reserved'] ?? null,
            $this->config['options']['retryInterval'] ?? 0,
            $this->config['options']['readTimeout'] ?? 0,
        );

        $this->client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

        if (isset($this->config['options']['prefix'])) {
            $this->client->setOption(Redis::OPT_PREFIX, $this->config['options']['prefix'] . ':');
        }

        if ($this->config['options']['parameters']['password'] ?? false) {
            $this->client->auth($this->config['options']['parameters']['password']);
        }
    }
}