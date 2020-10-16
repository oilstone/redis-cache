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
        $options = ['cluster' => 'predis'];

        if (isset($this->config['options']['prefix'])) {
            $options['prefix'] = $this->config['options']['prefix'] . ':';
        }

        $this->client = new Client(array_map(function ($seed) {
            return $this->resolveConnection($seed) . (isset($this->config['auth']['password']) ? '?password=' . $this->config['auth']['password'] : '');
        }, $this->config['connections'] ?? [[]]), $options);
    }
}