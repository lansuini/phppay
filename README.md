# 域名要求(接口和回调域名必须使用不相同的主域名)
- 回调域名
- 商户后台域名
- 接口层请求域名
- 管理后台域名

# 软件依赖
- php7
- mysql
- redis

# php扩展依赖
mysql
redis
swoole

# 配置
cp .env.stable.example .env

# 密钥修改
修改*_SALT配置，按原有长度修改

# redis配置
修改REDIS_*配置

# db配置
修改DB_*配置

# GM IP白名单控制
GM_IPWHITE=127.0.0.1,127.0.0.2

# 修改APP名称
APP_NAME

# 开关debug
APP_DEBUG

# 配置域名
修改*_DOMAIN地址

# 网关Ip请求限制开关
GATE_IP_PROTECT

# 日志路径配置
LOGGER_PATH

# composer 安装
composer install

# 生成mysql结构
    - 修改phinx.xml配置development部分数据库配置，要求数据库生成表和删表权限(.env配置的数据库权限不一样，要求不同的mysql账号)
    - 执行 vendor/bin/phinx migrate

# 启动进程/关闭/kill
    - cd service
    - ./start.sh
    - ./stop.sh
    - ./kill.sh