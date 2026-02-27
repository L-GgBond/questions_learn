<?php
declare(strict_types=1);

namespace App\Job;

use App\Service\MailService;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use RuntimeException;

/**
 * 邮件发送异步任务载体
 * * [⚠️ 极其重要的架构规范]
 * 1. 任务对象会被序列化 (serialize) 并存储到 Redis 中。
 * 2. 构造函数中【绝对禁止】传入包含 PDO、Socket 连接的 Service 实例。
 * 3. 只能传入标量数据 (String, Int, Array)。
 */
class SendMailJob extends Job
{
    /**
     * @var int 队列组件自带的重试机制：如果 handle() 抛出异常，最大重试 3 次
     */
    public int $maxAttempts = 3;

    /**
     * 接收业务所需的标量数据
     */
    public function __construct(
        public string $to,
        public string $subject,
        public string $htmlContent,
        public array $strategy
    ) {}

    /**
     * 消费者进程实际执行的业务逻辑
     * 该方法运行在与 HTTP 完全隔离的后台消费者进程 (ConsumerProcess) 中
     */
    public function handle(): void
    {
        // 1. 在消费者进程的生命周期内，动态从 DI 容器获取邮件服务实例
        $mailService = ApplicationContext::getContainer()->get(MailService::class);

        // 2. 调用高可用容灾发送
        $isSuccess = $mailService->sendWithFailover(
            $this->to,
            $this->subject,
            $this->htmlContent,
            $this->strategy
        );

        // 3. 兜底判定
        // 如果容灾策略全军覆没，手动抛出异常。
        // Hyperf 底层的 async-queue 捕获到异常后，会将该任务放回 Redis 的 delayed 队列，稍后进行重试。
        if (!$isSuccess) {
            throw new RuntimeException('邮件全量容灾渠道均降级失败，任务执行中止，触发队列重试机制');
        }
    }
}