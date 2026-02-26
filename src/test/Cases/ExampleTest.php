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

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use function Hyperf\Coroutine\run;

/**
 * @internal
 * @coversNothing
 */
class ExampleTest extends TestCase
{
    public function testExample()
    {
        \Hyperf\Coroutine\run(function () {
            // 1. 获取响应对象
            $response = $this->get('/');

            // 2. 将响应对象转换为数组（Hyperf 的 TestResponse 提供了便捷方法）
            $res = $response->json();

            // 3. 执行断言
            $this->assertIsArray($res);
            $this->assertSame('Hello Hyperf.', $res['message'] ?? '');
            $this->assertSame('GET', $res['method'] ?? '');
        });
    }

    public function testTrue()
    {
        $this->assertTrue(true);
    }
}
