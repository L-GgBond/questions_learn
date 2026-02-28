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

namespace App\Controller;


use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\Redis\Redis;


#[Controller("/redis")]
class RedisController extends AbstractController
{
    public function __construct(protected Redis $redis)
    {
    }

    #[GetMapping(path: "redis")]
    public function redis()
    {
        $this->redis->set("key", "value");
        return $this->responseJson->success();
    }
}
