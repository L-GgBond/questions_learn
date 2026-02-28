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

use App\Request\SendCodeRequest;
use App\Request\SignUpRequest;
use App\Service\JwtService;
use App\Service\UserService;
use App\Service\VerificationService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;

#[Controller("/auth")]
class AuthController extends AbstractController
{

    public function __construct(
        private VerificationService $verificationService,
        private UserService $userService,
        private JWTService $jwtService,
    ) {
    }

    /**
     * 接口 1：请求发送验证码
     */
    #[PostMapping(path: "send-code")]
    public function sendCode(SendCodeRequest $request)
    {
        $email = $this->request->input('email');
        // 大厂规范：必须要求前端传场景值，比如 register, reset_pwd, login
        $scene = $this->request->input('scene', 'login');

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

        $user = $this->userService->getUserByEmail($email);

        $tokenData = $this->jwtService->generateToken($user['id']);

        return $this->responseJson->success(['token' => $tokenData]);
    }

    /**
     * 注册.
     * @param SignUpRequest $signUpRequest
     * @return \Psr\Http\Message\ResponseInterface
     */
    #[PostMapping(path: "signup")]
    public function signup(SignUpRequest $signUpRequest)
    {
        // 获取已验证的数据
        $validated = $signUpRequest->validated();

       $this->userService->signup($validated);

        return $this->responseJson->success();
    }
}
