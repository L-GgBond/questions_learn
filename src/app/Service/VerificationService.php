<?php

namespace App\Service;

use App\Constants\ErrorCode;
use App\Constants\RedisKey;
use App\Exception\BusinessException;
use App\Job\SendMailJob;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Redis\Redis;
use Hyperf\Contract\TranslatorInterface;

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
        private DriverFactory $driverFactory,
        private TranslatorInterface $translator // 🚀 注入翻译组件
    ) {}


    /**
     * 申请并发送验证码
     */
    public function sendEmailCode(string $email, string $scene): void
    {
        // 1. 【防刷拦截】检查 60 秒冷却锁是否存在
        $lockKey = sprintf(RedisKey::EMAIL_LOCK, $email);
        if ($this->redis->exists($lockKey)) {
            // 🚀 国际化抛出：获取翻译文本，并动态传入 :seconds 变量！
            $msg = ErrorCode::getMessage(ErrorCode::AUTH_FREQ_LIMIT, ['seconds' => self::LOCK_TTL]);
            // 如果存在，直接抛出业务异常，拦截请求！
            throw new BusinessException($msg, ErrorCode::AUTH_FREQ_LIMIT);
        }

        // 2. 生成 6 位纯数字安全随机码
        $code = (string) mt_rand(100000, 999999);

        // 3. 将验证码存入 Redis，并设置 5 分钟过期
        $codeKey = sprintf(RedisKey::EMAIL_CODE, $scene, $email);
        $this->redis->setex($codeKey, self::EXPIRE_TTL, $code);

        // 4. 加上 60 秒的冷却锁，防止用户疯狂点击发送按钮
        $this->redis->setex($lockKey, self::LOCK_TTL, 'locked');

        // 1. 先把场景词翻译了 (比如 'login' 翻译成 '登录' 或 'Login')
        $sceneKey = "message.auth.scenes.{$scene}";
        $translatedScene = $this->translator->trans($sceneKey);
        // 防御性编程：如果字典里没配这个场景，就原样输出英文字符串兜底
        if ($translatedScene === $sceneKey) {
            $translatedScene = $scene;
        }

        // 2. 动态渲染邮件标题
        $subject = $this->translator->trans('message.auth.subject', [
            'scene' => $translatedScene
        ]);

        // 3. 动态渲染邮件 HTML 正文
        $htmlContent = $this->translator->trans('message.auth.body', [
            'scene' => $translatedScene,
            'code'  => $code
        ]);

        // 4. 将【已经翻译好的纯文本】推入后台队列
        $this->driverFactory->get('default')->push(
            new SendMailJob($email, $subject, $htmlContent, ['163', 'qq', 'gmail'])
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
            // 🚀 国际化抛出：过期或不存在
            $msg = ErrorCode::getMessage(ErrorCode::AUTH_CODE_EXPIRED);
            throw new BusinessException($msg, ErrorCode::AUTH_CODE_EXPIRED);
        }

        // 3. 校验是否匹配 (注意转成 string 强一致对比)
        if ((string)$realCode !== (string)$code) {
            // 🚀 国际化抛出：验证码错误
            $msg = ErrorCode::getMessage(ErrorCode::AUTH_CODE_ERROR);
            throw new BusinessException($msg, ErrorCode::AUTH_CODE_ERROR);
        }

        // 4. 🚀🚀🚀 【安全红线：防重放攻击】验证通过后，必须立刻销毁！
        $this->redis->del($codeKey);

        return true;
    }

}