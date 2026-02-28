<?php

namespace App\Service;
use App\Exception\BusinessException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Hyperf\Contract\ConfigInterface;
use Throwable;

class JwtService
{
    public function __construct(private ConfigInterface $config){}

    /**
     * 为用户签发 JWT 令牌
     *
     * @param int $userId 用户的主键 ID
     * @return array 包含 token 和有效期的数组
     */
    public function generateToken(int $userId): array
    {
        // 从配置文件或 .env 中读取密钥和有效期 (默认 2 小时)
        $secret = $this->config->get('jwt.secret', 'hyperf_enterprise_super_secret_key_2026');
        $ttl = (int) $this->config->get('jwt.ttl', 7200);
        $now = time();

        // 🚀 组装标准 Payload (载荷)
        $payload = [
            'iss' => 'hyperf-api',   // 签发者 (Issuer)
            'iat' => $now,           // 签发时间 (Issued At)
            'exp' => $now + $ttl,    // 过期时间 (Expiration Time)
            'uid' => $userId         // 自定义业务数据：用户 ID
        ];

        // 使用 HS256 算法和你的 Secret Key 进行哈希签名
        $token = JWT::encode($payload, $secret, 'HS256');

        return [
            'access_token' => $token,
            'expires_in'   => $ttl,
            'token_type'   => 'Bearer'
        ];
    }

    /**
     * 解析并校验 JWT 令牌
     *
     * @param string $token 前端传来的 Token
     * @return array 解码后的数据载荷
     */
    public function verifyToken(string $token): array
    {
        try {
            $secret = $this->config->get('jwt.secret', 'hyperf_enterprise_super_secret_key_2026');

            // 🚀 底层会自动验证签名是否被篡改，以及 exp 是否已过期
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            return (array) $decoded;
        } catch (Throwable $e) {
            // 只要解密失败（不管是被篡改还是过期），统一抛出 401 未授权
            throw new BusinessException('Token 无效或已过期，请重新登录', 401);
        }
    }

}