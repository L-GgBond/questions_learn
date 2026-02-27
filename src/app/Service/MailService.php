<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\BusinessException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * 邮件发送服务核心类
 * 采用多渠道驱动架构，支持动态容灾切换与连接复用
 */
class MailService
{
    /**
     * @var Mailer[] 缓存不同渠道的 Mailer 实例 (享元模式)
     * 在 Swoole 常驻内存模式下，复用实例可极大减少 TCP 握手开销
     */
    private array $mailers = [];

    /**
     * 依赖注入配置中心与日志组件
     * 采用 PHP 8 构造函数属性提升语法
     */
    public function __construct(
        private ConfigInterface $config,
        private StdoutLoggerInterface $logger
    ) {}

    /**
     * 单渠道发送 HTML 邮件
     *
     * @param string $to 收件人地址
     * @param string $subject 邮件主题
     * @param string $htmlContent 邮件正文 (HTML格式)
     * @param string|null $profile 指定发件渠道 (如 '163', 'qq')，传 null 则读取默认配置
     * @return bool 是否发送成功
     * @throws BusinessException 当请求的渠道未在配置中定义时抛出
     */
    public function sendHtml(string $to, string $subject, string $htmlContent, ?string $profile = null): bool
    {
        // 1. 确定使用的渠道配置
        $profile = $profile ?: $this->config->get('mail.default');
        $profileConfig = $this->config->get("mail.profiles.{$profile}");

        if (!$profileConfig) {
            throw new BusinessException("邮件驱动渠道 [{$profile}] 未配置", 500);
        }

        // 2. 组装符合 RFC 标准的 Email 对象
        $email = (new Email())
            ->from($this->buildFrom($profileConfig))
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        // 3. 提取对应的 Mailer 实例并执行发送
        return $this->doSend($this->getMailer($profile, $profileConfig['dsn']), $email, $profile);
    }

    /**
     * 高可用容灾发送 (大厂标配 Failover 机制)
     *
     * 遍历策略数组，主节点失败自动无缝降级到备用节点
     *
     * @param string $to 收件人地址
     * @param string $subject 邮件主题
     * @param string $htmlContent 邮件正文
     * @param array $fallbackProfiles 优先级降级策略，例如 ['163', 'qq', 'gmail']
     * @return bool 只要有一个渠道发送成功即返回 true，全量失败返回 false
     */
    public function sendWithFailover(string $to, string $subject, string $htmlContent, array $fallbackProfiles): bool
    {
        if (empty($fallbackProfiles)) {
            $this->logger->error('[MailService] 容灾策略渠道列表不能为空，拒绝执行');
            return false;
        }

        // 按照业务传入的优先级顺序进行轮询
        foreach ($fallbackProfiles as $profile) {
            $this->logger->info(sprintf('[MailService] 正在尝试使用 [%s] 渠道发送邮件...', $profile));

            // 调用单渠道发送方法
            if ($this->sendHtml($to, $subject, $htmlContent, $profile)) {
                // 发送成功，立刻中断轮询，业务正常向下流转
                return true;
            }

            // 发送失败，捕获并记录降级日志，循环将继续尝试下一个驱动
            $this->logger->warning(sprintf('[MailService] 渠道 [%s] 发生故障，触发自动降级，准备切换备用渠道', $profile));
        }

        // 所有渠道遍历完毕均未成功，触发最高级别告警 (全线熔断)
        $this->logger->critical('[MailService] 致命灾难：所有配置的邮件渠道均已瘫痪！');
        return false;
    }

    /**
     * 获取 Mailer 实例 (内部缓存工厂)
     *
     * @param string $profile 渠道标识
     * @param string $dsn 数据源连接字符串
     * @return Mailer
     */
    private function getMailer(string $profile, string $dsn): Mailer
    {
        if (!isset($this->mailers[$profile])) {
            // 解析 DSN (如 smtps://user:pass@host:port) 并初始化传输层
            $this->mailers[$profile] = new Mailer(Transport::fromDsn($dsn));
        }
        return $this->mailers[$profile];
    }

    /**
     * 组装发件人抬头
     */
    private function buildFrom(array $config): string
    {
        return $config['from_name']
            ? "{$config['from_name']} <{$config['from_address']}>"
            : $config['from_address'];
    }

    /**
     * 底层执行发送动作，并进行严密的异常接管
     */
    private function doSend(Mailer $mailer, Email $email, string $profile): bool
    {
        try {
            // 此处底层会触发 Swoole 协程的 Hook，进行非阻塞网络 I/O
            $mailer->send($email);

            $this->logger->info(sprintf(
                '[MailService] 邮件发送成功. Channel: %s, To: %s',
                $profile,
                implode(',', array_keys($email->getTo()))
            ));
            return true;
        } catch (TransportExceptionInterface $e) {
            // 捕获网络异常 (如 DNS 解析失败、端口不通、鉴权拒绝等)
            $this->logger->error(sprintf('[MailService][%s] SMTP网络失败: %s', $profile, $e->getMessage()));
        } catch (Throwable $e) {
            // 捕获其他未知致命错误
            $this->logger->error(sprintf('[MailService][%s] 发送发生致命错误: %s', $profile, $e->getMessage()));
        }

        return false;
    }
}