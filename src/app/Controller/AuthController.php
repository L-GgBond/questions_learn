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

use App\Service\VerificationService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;

#[Controller("/auth")]
class AuthController extends AbstractController
{

    public function __construct(
        private VerificationService $verificationService
    ) {
    }

    /**
     * 接口 1：请求发送验证码
     */
    #[PostMapping(path: "send-code")]
    public function sendCode()
    {
        $email = $this->request->input('email');
        // 大厂规范：必须要求前端传场景值，比如 register, reset_pwd, login
        $scene = $this->request->input('scene', 'login');

        if (empty($email)) {
            return $this->responseJson->fail(400, '邮箱不能为空');
        }

        // 直接调 Service，防刷、存 Redis、扔队列全在里面完成了！
        $this->verificationService->sendEmailCode($email, $scene);

        return $this->responseJson->success([], '验证码发送成功');
    }

    /**
     * 接口 2：用户提交表单（登录/注册）
     */
    #[PostMapping(path: "login")]
    public function login()
    {
        $email = $this->request->input('email');
        $code = $this->request->input('code');

        // 1. 调用 Service 进行极其严格的校验
        $this->verificationService->verifyEmailCode($email, $code, 'login');

        // 2. 校验走到这里说明绝对通过了，并且旧验证码已经被销毁了
        // 此处开始执行正常的查数据库、生成 JWT Token 等登录逻辑...

        return $this->responseJson->success(['token' => 'eyJhbGciOiJIUzI1...']);
    }

}
