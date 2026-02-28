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

use App\Request\LoginRequest;
use App\Request\SendCodeRequest;
use App\Request\SignUpRequest;
use App\Service\JwtService;
use App\Service\UserService;
use App\Service\VerificationService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;

#[Controller("/auth")]
class AuthController extends AbstractController
{

    public function __construct(
        private VerificationService $verificationService,
        private UserService $userService,
        private JwtService $jwtService,
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
    public function login(LoginRequest $request)
    {
        // 1. 获取经过严格校验的数据 (如拦截非法邮箱、限制密码长度)
        $credentials = $request->validated();

        // 2. 将认证逻辑全权交由 AuthService 处理
        $tokenData = $this->userService->login($credentials);

        return $this->responseJson->success(['token' => $tokenData],'登陆成功');
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

    /**
     * 退出登陆
     */
    #[PostMapping(path: "logout")]
    public function logout()
    {
        // 1. 从请求头提取 Bearer Token
        $token = $this->extractToken($this->request->header('Authorization'));

        if($token){
            // 2. 将 Token 加入黑名单使其立即失效
            $this->jwtService->invalidateToken($token);
        }

        // 3. 无论 Token 是否有效，为了防止暴露内部状态，统一返回成功
        return $this->responseJson->success([], '已安全退出');
    }

    public function extractToken(string $token): ?string
    {
//        if (str_starts_with($token, 'Bearer ')) {
//            return substr($token, 7);
//        }
//        return null;
        return $token;
    }
}
