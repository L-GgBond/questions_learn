<?php

namespace App\Service;

use App\Constants\RedisKey;
use App\Exception\BusinessException;
use App\Job\SendMailJob;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Redis\Redis;

/**
 * 企业级验证码中心服务
 */
class VerificationService
{
    // 配置参数
    private const EXPIRE_TTL = 300; // 验证码有效期 5 分钟 (300秒)
    private const LOCK_TTL = 60;    // 发送冷却时间 60 秒

    public function __construct(
        private Redis $redis,
        private DriverFactory $driverFactory
    ) {}


    /**
     * 申请并发送验证码
     */
    public function sendEmailCode(string $email, string $scene): void
    {
        // 1. 【防刷拦截】检查 60 秒冷却锁是否存在
        $lockKey = sprintf(RedisKey::EMAIL_LOCK, $email);
        if ($this->redis->exists($lockKey)) {
            // 如果存在，直接抛出业务异常，拦截请求！
            throw new BusinessException('操作太频繁，请 60 秒后再试', 429);
        }

        // 2. 生成 6 位纯数字安全随机码
        $code = (string) mt_rand(100000, 999999);

        // 3. 将验证码存入 Redis，并设置 5 分钟过期
        $codeKey = sprintf(RedisKey::EMAIL_CODE, $scene, $email);
        $this->redis->setex($codeKey, self::EXPIRE_TTL, $code);

        // 4. 加上 60 秒的冷却锁，防止用户疯狂点击发送按钮
        $this->redis->setex($lockKey, self::LOCK_TTL, 'locked');

        // 5. 拼装邮件内容并【异步投递】到队列
        $htmlContent = "<h3>您的【{$scene}】验证码是：<span style='color:red;'>{$code}</span></h3><p>验证码在 5 分钟内有效，请勿泄露给他人。</p>";
        $strategy = ['163', 'qq', 'gmail']; // 容灾策略

        $this->driverFactory->get('default')->push(
            new SendMailJob($email, '系统安全验证码', $htmlContent, $strategy)
        );
    }

    /**
     * 校验验证码 (极度严格)
     */
    public function verifyEmailCode(string $email, string $code, string $scene): bool
    {
        $codeKey = sprintf(RedisKey::EMAIL_CODE, $scene, $email);

        // 1. 从 Redis 中读取真实的验证码
        $realCode = $this->redis->get($codeKey);

        // 2. 校验是否存在或过期
        if (empty($realCode)) {
            throw new BusinessException('验证码已过期或不存在', 400);
        }

        // 3. 校验是否匹配 (注意转成 string 强一致对比)
        if ((string)$realCode !== (string)$code) {
            throw new BusinessException('验证码错误', 400);
        }

        // 4. 🚀🚀🚀 【安全红线：防重放攻击】验证通过后，必须立刻销毁！
        $this->redis->del($codeKey);

        return true;
    }

}