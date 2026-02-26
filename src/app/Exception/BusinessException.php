<?php

namespace App\Exception;

use Hyperf\Server\Exception\ServerException;
use Throwable;

class BusinessException extends ServerException
{
    // 增加默认值，业务抛出时只需 throw new BusinessException(1001, '金币不足');
    public function __construct(string $message = 'Business Error', int $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}