<?php
declare(strict_types=1);

namespace App\Components;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\ResponseInterface;

class Response
{
    #[Inject]
    protected ResponseInterface $response;

    /**
     * 成功响应
     * @param array|object $data
     */
    public function success(mixed $data = [], string $msg = 'success'): \Psr\Http\Message\ResponseInterface
    {
        return $this->response->json([
            'code' => 0,
            'data' => $data,
            'msg' => $msg,
        ]);
    }

    /**
     * 失败响应
     * @param int $code
     * @param string $message
     * @return ResponseInterface
     */
    public function fail(int $code, string $message = ''): \Psr\Http\Message\ResponseInterface
    {
        return $this->response->json([
            'code' => $code,
            'message' => $message,
        ]);
    }

}