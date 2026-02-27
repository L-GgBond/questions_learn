<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    // 注册异步队列的消费者进程，系统启动时会自动拉起它！
    Hyperf\AsyncQueue\Process\ConsumerProcess::class,
];
