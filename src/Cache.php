<?php

/**
 * @noinspection PhpRedundantCatchClauseInspection
 * @noinspection PhpUnused
 */

namespace Oilstone\RedisCache;

use Illuminate\Support\Str;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Oilstone\GlobalClasses\MakeGlobal;
use Oilstone\Logging\Log;
use Redis;
use RedisCluster;
use RedisClusterException;
use RedisException;

/**
 * Class Cache
 * @package Oilstone\RedisCache
 */
class Cache extends MakeGlobal
{
    /**
     * @var Cache
     */
    protected static $instance;

    /**
     * @var bool
     */
    protected $enabled = false;

    /**
     * @var bool
     */
    protected $logging = false;

    /**
     * @var Redis|RedisCluster
     */
    protected $client;

    /**
     * @var array|null
     */
    protected $connectionString;

    /**
     * @var array|null
     */
    protected $options;

    /**
     * @var string|null
     */
    protected $prefix;

    /**
     * @var Log
     */
    protected $log;

    /**
     * Cache constructor.
     * @param mixed|null $connectionString
     * @param mixed|null $options
     * @param null $prefix
     */
    public function __construct($connectionString = null, $options = null, $prefix = null)
    {
        $this->connectionString = $connectionString;
        $this->options = $options;
        $this->prefix = $prefix;

        if (!$connectionString) {
            return;
        }

        if (is_array($connectionString) && count($connectionString) === 1) {
            $connectionString = $connectionString[0];
        }

        if (isset($options['logging'])) {
            $this->logging = boolval($options['logging']);
            $this->log = Log::instance();

            if ($options['logPath'] ?? false) {
                $logger = new Logger($options['logName'] ?? 'redis');
                $logger->pushHandler(new RotatingFileHandler($options['logPath'], 10, $options['logLevel'] ?? Logger::DEBUG));

                $this->log = new Log($logger);
                $this->log->enable();
            }
        }

        try {
            if (is_array($connectionString)) {
                $this->client = new RedisCluster(null, array_map(function ($node): string {
                    $node = parse_url($node);

                    return $node['host'] . ':' . ($node['port'] ?? 6379);
                }, $connectionString), $options['timeout'] ?? 1, $options['readTimeout'] ?? 1);
            } else {
                $this->client = new Redis();

                $connectionString = parse_url($connectionString);

                $this->client->connect(
                    $connectionString['host'],
                    $connectionString['port'] ?? 6379,
                    $options['timeout'] ?? 1,
                    $connectionString['reserved'] ?? null,
                    $connectionString['retryInterval'] ?? 0,
                    $options['readTimeout'] ?? 1
                );
            }

            $this->enable();
        } catch (RedisException $e) {
            $this->disable();

            $this->logEntry($e->getMessage(), 'error');
        } catch (RedisClusterException $e) {
            $this->disable();

            $this->logEntry($e->getMessage(), 'error');
        }
    }

    /**
     * @return void
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * @param string $logEntry
     * @param string $level
     */
    protected function logEntry(string $logEntry, string $level = 'info'): void
    {
        if ($this->logging && $this->log) {
            $this->log->{$level}($logEntry);
        }
    }

    /**
     * @param string $name
     * @param $value
     * @param int|null $minutes
     */
    public static function set(string $name, $value, ?int $minutes = null)
    {
        if (static::instance()) {
            static::instance()->put($name, $value, $minutes);
        }
    }

    /**
     * @return Cache|null
     */
    public static function instance(): ?Cache
    {
        return static::$instance && static::$instance->enabled() ? static::$instance : null;
    }

    /**
     * @return bool
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param string $name
     * @param $value
     * @param int|null $minutes
     */
    public static function put(string $name, $value, ?int $minutes = null)
    {
        if (static::instance()) {
            try {
                $name = static::sanitizeKey($name);

                if (isset($minutes)) {
                    static::instance()->client->setex($name, $minutes * 60, serialize($value));
                } else {
                    static::instance()->client->set($name, serialize($value));
                }

                static::instance()->logEntry('Set cache key ' . $name, 'info');
            } catch (RedisException $e) {
                static::instance()->disable();

                static::instance()->logEntry($e->getMessage(), 'error');
            } catch (RedisClusterException $e) {
                static::instance()->disable();

                static::instance()->logEntry($e->getMessage(), 'error');
            }
        }
    }

    /**
     * @param string $name
     * @return string
     */
    protected static function sanitizeKey(string $name): string
    {
        if (static::instance()) {
            $name = static::instance()->getPrefix() . ':' . $name;
        }

        return implode(':', array_map(function ($name) {
            return trim(Str::slug($name));
        }, explode(':', trim($name, ':'))));
    }

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @param string|null $prefix
     * @return Cache
     */
    public function setPrefix(?string $prefix): Cache
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return void
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * @param string $string
     * @return mixed
     */
    public static function pull(string $string)
    {
        $data = static::get($string);

        static::delete($string);

        return $data;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public static function get(string $name)
    {
        if (static::instance()) {
            try {
                $name = static::sanitizeKey($name);

                $value = unserialize(static::instance()->client->get($name));

                static::instance()->logEntry('Get cache key ' . $name, 'info');

                return $value;
            } catch (RedisException $e) {
                static::instance()->disable();

                static::instance()->logEntry($e->getMessage(), 'error');
            } catch (RedisClusterException $e) {
                static::instance()->disable();

                static::instance()->logEntry($e->getMessage(), 'error');
            }
        }

        return null;
    }

    /**
     * @param string $name
     */
    public static function delete(string $name)
    {
        if (static::instance()) {
            try {
                $name = static::sanitizeKey($name);

                static::instance()->client->unlink($name);

                static::instance()->logEntry('Delete cache key ' . $name, 'info');
            } catch (RedisException $e) {
                static::instance()->disable();

                static::instance()->logEntry($e->getMessage(), 'error');
            } catch (RedisClusterException $e) {
                static::instance()->disable();

                static::instance()->logEntry($e->getMessage(), 'error');
            }
        }
    }

    /**
     * @param string $name
     * @param callable $createCallback
     * @param int|null $minutes
     * @param bool $cacheOnNull
     * @return mixed
     */
    public static function fetchOrCreate(string $name, callable $createCallback, ?int $minutes = null, bool $cacheOnNull = false)
    {
        if (static::has($name)) {
            return static::get($name);
        }

        $value = $createCallback();

        if (!is_null($value) || $cacheOnNull) {
            static::put($name, $value, $minutes);
        }

        return $value;
    }

    /**
     * @param $name
     * @return int
     */
    public static function has(string $name): int
    {
        if (static::instance()) {
            try {
                $name = static::sanitizeKey($name);

                $exists = static::instance()->client->exists($name);

                static::instance()->logEntry('Check for cache key ' . $name, 'info');

                return $exists;
            } catch (RedisException $e) {
                static::instance()->disable();

                static::instance()->logEntry($e->getMessage(), 'error');
            } catch (RedisClusterException $e) {
                static::instance()->disable();

                static::instance()->logEntry($e->getMessage(), 'error');
            }
        }

        return 0;
    }

    /**
     * @param $name
     * @return string
     */
    public function __get($name)
    {
        if (static::instance()) {
            return $this->client->get(static::sanitizeKey($name));
        }

        return null;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (static::instance()) {
            $this->client->setex(static::sanitizeKey($name), $value, 3600);
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (static::instance()) {
            return $this->client->{$name}(...$arguments);
        }

        return null;
    }

    /**
     * @return Redis|RedisCluster
     */
    public function client()
    {
        return $this->client;
    }
}