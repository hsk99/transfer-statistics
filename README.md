

# TransferStatistics


TransferStatistics 使用[webman](https://github.com/walkor/webman)开发的一个应用监控系统，用于查看应用调用记录、请求量、调用耗时、调用分析等。


系统使用 `HTTP` 接收上报数据；使用 `Redis` 进行数据汇总统计；使用 `MySql` 存储统计数据和上报信息



# 所需环境


PHP版本不低于7.2，并安装 Redis 拓展



# 安装


## composer安装


创建项目


`composer create-project hsk99/transfer-statistics`


## 下载安装


1、下载 或 `git clone https://github.com/hsk99/transfer-statistics`


2、执行命令 `composer install`


## 配置修改


1、修改文件 `config/redis.php` 设置 Redis


2、修改文件 `config/server.php` 设置 HTTP


3、修改目录 `config/plugin/webman/redis-queue/` 设置 RedisQueue 相关信息



## 



# 运行


执行命令 `php start.php start`



# 查看统计


浏览器访问 `http://ip地址:8788`



# 上报数据


- [webman](https://github.com/walkor/webman) 使用 [webman-statistic](https://github.com/hsk99/webman-statistic) 插件

