<?php
declare(strict_types=1);

return [
    'default' => [
        // 默认使用 Redis 作为队列驱动引擎
        'driver' => Hyperf\AsyncQueue\Driver\RedisDriver::class,
        'redis' => [
            'pool' => 'default',
        ],
        'channel' => 'queue', // Redis 里的 list 键名
        'timeout' => 2,       // Redis 阻塞获取任务的超时时间
        'retry_seconds' => 5, // 失败后重试的间隔秒数
        'handle_timeout' => 10, // 任务执行的超时时间（比如发邮件最多等 10 秒）
        'processes' => 1,     // 启动多少个消费者进程（高并发可调大）
        'concurrent' => [
            'limit' => 5,     // 每个进程内同时开启的协程数（并发消费）
        ],
    ],
];