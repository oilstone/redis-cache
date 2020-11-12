<?php

/**
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnused
 * @noinspection PhpMissingFieldTypeInspection
 */

namespace Oilstone\RedisCache\Managers;

use RedisCluster;
use RedisClusterException;

/**
 * Class PhpRedisCluster
 * @package Oilstone\RedisCache\Managers
 */
class PhpRedisCluster extends PhpRedis
{
    /**
     * @var RedisCluster
     */
    protected $client;

    /**
     * @var bool
     */
    protected $appendPort = true;

    /**
     * @return void
     * @throws RedisClusterException
     */
    protected function connect(): void
    {
        $this->client = new RedisCluster(
            $this->config['name'] ?? null,
            array_map(function ($seed) {
                return $this->resolveConnection($seed);
            }, $this->config['connections'] ?? [[]]),
            $this->config['options']['timeout'] ?? null,
            $this->config['options']['read_timeout'] ?? null,
            $this->config['options']['persistent'] ?? false,
            $this->config['auth']['password'] ?? null
        );

        $this->client->setOption(RedisCluster::OPT_SERIALIZER, RedisCluster::SERIALIZER_PHP);

        if ($this->config['options']['prefix'] ?? false) {
            $this->client->setOption(RedisCluster::OPT_PREFIX, $this->config['options']['prefix'] . ':');
        }

        $this->client->setOption(RedisCluster::OPT_SLAVE_FAILOVER, $this->config['options']['slave_failover'] ?? RedisCluster::FAILOVER_ERROR);
    }
}