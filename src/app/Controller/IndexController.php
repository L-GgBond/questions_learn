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

use App\Constants\ErrorCode;
use App\Exception\BusinessException;
use Hyperf\Context\Context;
use Hyperf\Coroutine\Coroutine;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use function Hyperf\Translation\trans;

#[Controller("/index")]
class IndexController extends AbstractController
{

    public $a;

    #[GetMapping(path: "")]
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    #[GetMapping(path: "info/{id:\d+}")]
    public function info(int $id)
    {
        if ($id <= 0) {
            $msg = ErrorCode::getMessage(ErrorCode::PARAM_REQUIRED, ['field' => '武器ID']);
            throw new BusinessException($msg, ErrorCode::PARAM_REQUIRED);
        }

        return $this->responseJson->success($id);
    }

    #[GetMapping(path: "test")]
    public function test()
    {
//        dump(convert_size(memory_get_usage(true)));
        return convert_size(memory_get_usage(true));
    }

    #[GetMapping(path: "demo")]
    public function demo()
    {
        $a = $this->request->input('a');

        if ($a) {
            $this->a = $a;
        }

        return [
            'co_is' => Coroutine::inCoroutine(), // 判断当前是否在协程内
            'co_id' => Coroutine::id(), // 获取当前协程 id
            'a' => $this->a,
        ];

    }

    #[GetMapping(path: "demo1")]
    public function demo1()
    {
        $a = $this->request->input('a');

        Context::set('a', $a);

        return [
            'co_is' => Coroutine::inCoroutine(), // 判断当前是否在协程内
            'co_id' => Coroutine::id(), // 获取当前协程 id
            'a' => Context::get('a'),
        ];

    }

    #[GetMapping(path: "demodb")]
    public function demodb()
    {
        $result = Db::select('SELECT * FROM email_code;');
        return $this->responseJson->success($result);
    }
}
