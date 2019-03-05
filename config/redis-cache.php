<?php

return [

    /**
     * Example IP list: tcp://xxx.xxx.xxx.001:6379?alias=redis-host-1,tcp://xxx.xxx.xxx.002:6380?alias=redis-host-2
     */
    'ips' => env('REDIS_CACHE_IPS'),

];