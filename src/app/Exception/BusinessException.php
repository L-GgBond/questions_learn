<?php

namespace App\Exception;

use Hyperf\Server\Exception\ServerException;
use Throwable;

class BusinessException extends ServerException
{
    // 业务抛出时只需 throw new BusinessException('金币不足', 1001);
    // 或者只传提示语：throw new BusinessException('id无效');
    public function __construct(string $message = 'Business Error', int $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}