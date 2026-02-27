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

namespace App\Exception\Handler;

use App\Exception\BusinessException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Hyperf\Validation\ValidationException;
use Throwable;
use function Hyperf\Config\config;

class AppExceptionHandler extends ExceptionHandler
{
    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {

        // 1. 拦截业务异常 (无需打日志)
        if($throwable instanceof BusinessException) {
            // 阻止异常继续传播
            $this->stopPropagation();
            return $this->formatJson($response, $throwable->getCode(), $throwable->getMessage());
        }

        // 2. 拦截表单验证异常 (极其重要，防止脏数据报警)
        if ($throwable instanceof ValidationException) {
            $this->stopPropagation();
            // 提取第一条验证失败的提示语
            $message = $throwable->validator->errors()->first();
            return $this->formatJson($response,422, $message);
        }


        // 3. 拦截 HTTP 异常 (如 404, 405)
        if($throwable instanceof HttpException){
            $this->stopPropagation();
            // 注意：HTTP 异常必须用 getStatusCode()，否则拿不到 404
            $message = $throwable->getMessage() ?: 'Not Found';
            return $this->formatJson($response, $throwable->getStatusCode(), $message);
        }


        // 4.兜底处理系统级别异常 (记录日志并对外隐藏具体错误)
        $this->stopPropagation();


        // 记录详尽的日志：包含具体位置与完整堆栈
        $this->logger->error(sprintf(
            "System Error: %s in %s:%d\nStack Trace:\n%s",
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString()
        ));

        // 线上环境隐藏真实错误信息
        $errorMessage = config('app_env', 'prod') === 'dev' ? $throwable->getMessage() : '服务器开小差了，请稍后再试';

        return $this->formatJson($response,500, $errorMessage);


        /**
        $formatter = ApplicationContext::getContainer()->get(FormatterInterface::class);
        if($throwable instanceof BusinessException) {
            return $this->$response->fail($throwable->getMessage(), $throwable->getCode());
        }

        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());
        return $response->withHeader('Server', 'Hyperf')->withStatus(500)->withBody(new SwooleStream('Internal Server Error.'));

         **/
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }


    /**
     * 专属的底层 JSON 格式化方法，绝对安全，不依赖协程上下文
     */
    private function formatJson(ResponseInterface $response, int $code, string $message): ResponseInterface
    {
        $body = json_encode(['code' => $code, 'message' => $message], JSON_UNESCAPED_UNICODE);

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus(200) // 业务上统一返回 200 HTTP 状态码，具体错看 json 的 code
            ->withBody(new SwooleStream($body));
    }
}
