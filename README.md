docker-compose run --rm app composer create-project --prefer-dist laravel/laravel:^7.0 .
docker-compose run --rm app composer create-project topthink/think . --no-security-blocking

# 1. 赋予权限（防止 Laravel 报日志写入错误）
chmod -R 777 src/storage src/bootstrap/cache

# 2. 启动容器
docker-compose up -d

# 快速重启
docker-compose restart

# 彻底重建
docker-compose down
docker-compose up -d

# 导出
docker exec -i 0a92da6dbde2 mysqldump -u root -p123456 litemall > /Users/a404/python/php/mcshop/litemall_backup.sql

# 清理一次路由缓存
docker-compose exec app php artisan route:clear


# 连接到我运行 Docker 的这台 Mac 电脑
host.docker.internal

cp src/.example.env src/.env

# runtime 是 TP6 存放日志、缓存的地方，必须可写
chmod -R 777 src/runtime


# 删除 src 并重建，确保没有任何隐藏文件
rm -rf src && mkdir src
