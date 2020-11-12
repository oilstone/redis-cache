<?php

/**
 * @noinspection PhpUnused
 */

namespace Oilstone\RedisCache\Managers;

use Predis\Client;

/**
 * Class PredisCluster
 * @package Oilstone\RedisCache\Managers
 */
class PredisCluster extends Predis
{
    /**
     * @return void
     */
    protected function connect(): void
    {
        $options = ['cluster' => 'redis'];
        $connections = array_map(function ($connection) {
            return $this->resolveConnection($connection);
        }, $this->config['connections'] ?? [[]]);

        if ($this->config['options']['prefix'] ?? false) {
            $options['prefix'] = $this->config['options']['prefix'] . ':';
        }

        if (isset($connections[0]['password'])) {
            $options['parameters']['password'] = $connections[0]['password'];
        }

        if (isset($connections[0]['scheme'])) {
            $options['parameters']['scheme'] = $connections[0]['scheme'];
        }

        $this->client = new Client($connections, $options);
    }
}