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
    protected static $enabled = true;

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
     * Cache constructor.
     * @param mixed|null $parameters
     * @param mixed|null $options
     */
    public function __construct($parameters = null, $options = null)
    {
        $this->client = new Client($parameters, $options);

        try {
            $this->client->connect();
        } catch (Exception $exception) {
            static::disable();
        }

        $this->parameters = $parameters;

        $this->options = $options;
    }

    /**
     * @return void
     */
    public static function disable()
    {
        static::$enabled = false;
    }

    /**
     * @param $name
     * @return int
     */
    public static function has(string $name): int
    {
        if (static::instance()) {
            return static::instance()->client->exists(static::sanitizeKey($name));
        }

        return 0;
    }

    /**
     * @return Cache|null
     */
    public static function instance(): ?Cache
    {
        return static::enabled() ? static::$instance : null;
    }

    /**
     * @return bool
     */
    public static function enabled(): bool
    {
        return static::$enabled;
    }

    /**
     * @param string $name
     * @return string
     */
    protected static function sanitizeKey(string $name): string
    {
        return trim(Str::slug($name));
    }

    /**
     * @return void
     */
    public static function enable()
    {
        static::$enabled = true;
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
     * @param string $name
     * @param $value
     * @param int|null $minutes
     */
    public static function put(string $name, $value, ?int $minutes = null)
    {
        if (static::instance()) {
            static::instance()->client->set(static::sanitizeKey($name), serialize($value));

            if (isset($minutes)) {
                static::instance()->client->expire(static::sanitizeKey($name), $minutes * 60);
            }
        }
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
            return unserialize(static::instance()->client->get(static::sanitizeKey($name)));
        }

        return null;
    }

    /**
     * @param string $name
     */
    public static function delete(string $name)
    {
        if (static::instance()) {
            static::instance()->client->expireat(static::sanitizeKey($name), 0);
        }
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