<?php

namespace App\Constants;

class RedisKey
{
    /**
     * 验证码存储 Key
     * 格式：email_code:{场景}:{邮箱} (例如：email_code:register:test@qq.com)
     */
    public const EMAIL_CODE = 'email_code:%s:%s';

    /**
     * 发送频率防刷锁 Key
     * 格式：email_lock:{邮箱}
     */
    public const EMAIL_LOCK = 'email_lock:%s';
}