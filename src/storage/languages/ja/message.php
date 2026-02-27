<?php

return [
    'invalid_id' => '無効なユーザーID',
    'coin_not_enough' => 'ゲームコインが不足しています',
    'welcome' => 'ゲームへようこそ、:name',
    'param_required' => '缺少必要参数 ja: :field',
    'auth' => [
        // 邮件标题模板
        'subject' => 'ja【:scene】安全验证码',
        // 邮件正文模板
        'body'    => '<h3>ja您的【:scene】验证码是：<span style="color:red;">:code</span></h3><p>验证码在 5 分钟内有效，请勿泄露给他人。</p>',
        // 业务场景的动态翻译
        'scenes'  => [
            'login'    => 'ja登录',
            'register' => 'ja注册',
            'reset'    => 'ja重置密码',
        ],
        'freq_limit'   => 'ja操作太频繁，请 :seconds 秒后再试',
        'code_expired' => 'ja验证码已过期或不存在',
        'code_error'   => 'ja验证码错误',
    ],
];