<?php

/** @noinspection PhpUndefinedFunctionInspection */

namespace Oilstone\RedisCache\Integrations\Laravel;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Oilstone\RedisCache\Cache;

/**
 * Class ServiceProvider
 * @package Oilstone\RedisCache\Integrations\Laravel
 */
class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../../../config/redis-cache.php';

        $this->mergeConfigFrom($configPath, 'redis-cache');

        // Create a new cache instance
        $cache = new Cache(explode(",", config('redis-cache.ips')), ['cluster' => 'redis']);

        // Make the defined cache instance global
        $cache->setAsGlobal();
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../../config/redis-cache.php';

        $this->publishes([$configPath => $this->getConfigPath()], 'config');
    }

    /**
     * Get the config path
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return config_path('redis-cache.php');
    }

    /**
     * Publish the config file
     *
     * @param  string $configPath
     */
    protected function publishConfig($configPath)
    {
        $this->publishes([$configPath => config_path('redis-cache.php')], 'config');
    }
}