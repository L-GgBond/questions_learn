# 使用 PHP 8.1 的 Swoole 官方镜像
FROM hyperf/hyperf:8.1-alpine-v3.16-swoole

WORKDIR /var/www

# PHP 8.1 镜像通常已经内置了绝大多数扩展，按需添加即可
RUN apk add --no-cache \
    libstdc++ \
    openssl \
    php81-pdo_mysql \
    php81-redis \
    php81-bcmath

# 设置时区
RUN ln -sf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo "Asia/Shanghai" > /etc/timezone

# ✅ Hyperf 3.1 依然要求关闭 shortname
RUN echo "swoole.use_shortname = 'Off'" >> /etc/php81/conf.d/swoole.ini

EXPOSE 9501

ENTRYPOINT ["php", "/var/www/bin/hyperf.php", "start"]