<?php

namespace Oilstone\RedisCache;

use Exception;
use Illuminate\Support\Str;
use Oilstone\GlobalClasses\MakeGlobal;
use Predis\Client;

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
     * @var Client
     */
    protected $client;

    /**
     * @var array|null
     */
    protected $parameters;

    /**
     * @var array|null
     */
    protected $options;

    /**
     * @var string|null
     */
    protected $prefix;

    /**
     * Cache constructor.
     * @param mixed|null $parameters
     * @param mixed|null $options
     * @param null $prefix
     */
    public function __construct($parameters = null, $options = null, $prefix = null)
    {
        $this->client = new Client($parameters, $options);

        try {
            $this->client->connect();

            $this->enable();
        } catch (Exception $exception) {
            //
        }

        $this->parameters = $parameters;
        $this->options = $options;
        $this->prefix = $prefix;
    }

    /**
     * @return void
     */
    public function enable()
    {
        $this->enabled = true;
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
                static::instance()->client->set(static::sanitizeKey($name), serialize($value));

                if (isset($minutes)) {
                    static::instance()->client->expire(static::sanitizeKey($name), $minutes * 60);
                }
            } catch (Exception $e) {
                static::instance()->disable();
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
                return unserialize(static::instance()->client->get(static::sanitizeKey($name)));
            } catch (Exception $e) {
                static::instance()->disable();
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
                static::instance()->client->expireat(static::sanitizeKey($name), 0);
            } catch (Exception $e) {
                static::instance()->disable();
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
                return static::instance()->client->exists(static::sanitizeKey($name));
            } catch (Exception $e) {
                static::instance()->disable();
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
            $this->client->set(static::sanitizeKey($name), $value);
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
     * @return Client
     */
    public function client(): Client
    {
        return $this->client;
    }
}