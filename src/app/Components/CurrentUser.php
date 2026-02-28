<?php

namespace App\Components;

use App\Exception\BusinessException;
use App\Model\User;
use Hyperf\Context\Context;
use Hyperf\HttpServer\Contract\RequestInterface;

class CurrentUser
{
    public function __construct(
        protected RequestInterface $request
    ) {}

    /**
     * 获取当前登录用户的 ID
     */
    public function id(): int
    {
        $uid = $this->request->getAttribute('user_id');
        if (! $uid) {
            throw new BusinessException('未获取到登录状态', 401);
        }
        return (int) $uid;
    }

    /**
     * 延迟加载：获取当前登录用户的完整模型信息
     */
    public function info(): User
    {
        // 1. 定义一个唯一的协程上下文 Key
        $contextKey = 'current_user_model';

        // 2. 如果当前协程(本次 HTTP 请求)里已经查过该用户了，直接从内存返回，不再查库
        if (Context::has($contextKey)) {
            return Context::get($contextKey);
        }

        // 3. 真正去查数据库
        $user = User::find($this->id());
        if (! $user) {
            throw new BusinessException('用户不存在或已注销');
        }

        // 4. 将查到的用户信息存入当前协程上下文，供后续其他逻辑复用
        Context::set($contextKey, $user);

        return $user;
    }

}