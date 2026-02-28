<?php

namespace App\Service;
use App\Exception\BusinessException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;
use Throwable;

class JwtService
{
    // 定义 Redis 黑名单的前缀
    private const BLACKLIST_PREFIX = 'jwt:blacklist:';

    public function __construct(
        private ConfigInterface $config,
        private Redis $redis
    ){}

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
            // 1. 首先检查是否在黑名单中 (前置拦截，极速响应)
            $cacheKey = self::BLACKLIST_PREFIX . md5($token);
            if ($this->redis->exists($cacheKey)) {
                throw new BusinessException('登录已失效，请重新登录', 401);
            }

            $secret = $this->config->get('jwt.secret', 'hyperf_enterprise_super_secret_key_2026');

            // 🚀 底层会自动验证签名是否被篡改，以及 exp 是否已过期
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            return (array) $decoded;
        }catch (BusinessException $e){
            // 🚀 第一层拦截：如果是我们自己抛出的业务异常，直接原样抛出，保留精确的错误提示
            throw $e;
        } catch (Throwable $e) {
            // 🚀 第二层拦截：如果是 JWT 解密失败、或者发生了底层的 TypeError 致命错误，统一兜底为 401
            // 只要解密失败（不管是被篡改还是过期），统一抛出 401 未授权
            throw new BusinessException('Token 无效或已过期，请重新登录', 401);
        }
    }

    /**
     * 注销 Token（加入 Redis 黑名单）
     */
    public function invalidateToken(string $token): void
    {
        try {
            // 解析 Token 获取载荷（如果不抛异常说明 Token 本身还是合法的）
            $secret = $this->config->get('jwt.secret','hyperf_enterprise_super_secret_key_2026');
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            $now = time();
            $exp = $decoded->exp;
            $remainingTime = $exp - $now;

            // 如果 Token 还没有过期，才需要加入黑名单
            if($remainingTime > 0){
                // 使用 token 的 md5 散列值作为 key，防止 key 过长
                $cacheKey = self::BLACKLIST_PREFIX . md5($token);

                // 存入 Redis，并精准设置 TTL 为 Token 的剩余寿命
                // 这样当 Token 自然过期时，Redis 里的黑名单也会自动清理，绝不浪费内存
                $this->redis->setex($cacheKey, $remainingTime, 'invalidated');
            }
        }catch (Throwable $e){
            // 如果解析失败（已经过期或被篡改），不需要做任何处理，静默放行即可
            return;
        }
    }

}