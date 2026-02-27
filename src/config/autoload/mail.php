<?php
declare(strict_types=1);

use function Hyperf\Support\env;

return [
    // 默认使用的渠道
    'default' => 'qq',

    // 渠道列表
    'profiles' => [
        'qq' => [
            'dsn' => env('MAIL_QQ_DSN'),
            'from_address' => env('MAIL_QQ_FROM_ADDRESS'),
            'from_name'    => env('MAIL_QQ_FROM_NAME'),
        ],
        'gmail' => [
            'dsn' => env('MAIL_GMAIL_DSN'),
            'from_address' => env('MAIL_GMAIL_FROM_ADDRESS'),
            'from_name'    => env('MAIL_GMAIL_FROM_NAME'),
        ],
        '163' => [
            'dsn' => env('MAIL_163_DSN'),
            'from_address' => env('MAIL_163_FROM_ADDRESS'),
            'from_name'    => env('MAIL_163_FROM_NAME'),
        ],
    ]
];