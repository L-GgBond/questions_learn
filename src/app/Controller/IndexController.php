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

use App\Exception\BusinessException;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller("/index")]
class IndexController extends AbstractController
{
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
        if($id <= 0){
            throw new BusinessException(message: "id无效");
        }
        return $this->responseJson->success($id);
    }
}
