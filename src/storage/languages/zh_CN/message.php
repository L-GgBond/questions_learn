<?php

return [
    'invalid_id' => '用户ID无效',
    'coin_not_enough' => '游戏金币不足',
    'welcome' => '欢迎来到游戏, :name', // 支持变量替换
    'param_required' => '缺少必要参数: :field',
    'auth' => [
        // 邮件标题模板
        'subject' => 'zh_cn【:scene】安全验证码',
        // 邮件正文模板
        'body'    => '<h3>zh_cn您的【:scene】验证码是：<span style="color:red;">:code</span></h3><p>验证码在 5 分钟内有效，请勿泄露给他人。</p>',
        // 业务场景的动态翻译
        'scenes'  => [
            'login'    => 'zh_cn登录',
            'register' => 'zh_cn注册',
            'reset'    => 'zh_cn重置密码',
        ],
        'freq_limit'   => '操作太频繁，请 :seconds 秒后再试',
        'code_expired' => '验证码已过期或不存在',
        'code_error'   => '验证码错误',
    ],
];