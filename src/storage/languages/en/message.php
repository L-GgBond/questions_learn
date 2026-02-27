<?php

return [
    'invalid_id' => 'Invalid User ID',
    'coin_not_enough' => 'Insufficient game coins',
    'welcome' => 'Welcome to the game, :name',
    'param_required' => '缺少必要参数 en: :field',
    'auth' => [
        // 邮件标题模板
        'subject' => 'en【:scene】安全验证码',
        // 邮件正文模板
        'body'    => '<h3>en您的【:scene】验证码是：<span style="color:red;">:code</span></h3><p>验证码在 5 分钟内有效，请勿泄露给他人。</p>',
        // 业务场景的动态翻译
        'scenes'  => [
            'login'    => 'en登录',
            'register' => 'en注册',
            'reset'    => 'en重置密码',
        ],
        'freq_limit'   => 'Too many requests. Please try again in :seconds seconds.',
        'code_expired' => 'The verification code has expired or does not exist.',
        'code_error'   => 'Invalid verification code.',
    ],
];