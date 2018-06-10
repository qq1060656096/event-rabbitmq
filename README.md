# RabbitMQ Event

[查看大图](https://raw.githubusercontent.com/qq1060656096/event-rabbitmq/master/dev/images/event-rabbmitmq.png)
![Event RabbitMQ流程图](dev/images/event-rabbmitmq.small.png?dev/images/event-rabbmitmq.png)

# 安装(Install)
=========================

### 1步 通过Composer安装
-------------------------
> 通过 Composer 安装
如果还没有安装 Composer，你可以按 [getcomposer.org](https://getcomposer.org/) 中的方法安装


### 2步 创建composer写入内容
-------------------------
> 创建composer.json文件,并写入以下内容

```php
{
	"require-dev": {
		"zwei/rabbitmq-event": "dev-master"
    }
}	
```


### 3步 安装
-------------------------
```php
composer install
```

# 事件格式

```json
{
    "_id":"1528597263-0.0.0.0-2988-2",
    "eventKey":"event_demo_user_register_success",
    "data":{
        "date":"2018-06-10 10:21:03",
        "runId":2
    },
    "ip":"0.0.0.0"
}
```

# 运行脚本

## 网关分发
```bash
# 网关分发
php vendor/bin/zwei/event-rabbitmq/run-gateway-service.php
```
## 普通队列消费事件
```bash
# 网管测试
php vendor/bin/zwei/event-rabbitmq/run-standard-service.php rabbit_queue_single
```

## 发送测试事件脚本
> php vendor/bin/zwei/event-rabbitmq/run-send-test-event-message.php 事件key 运行次数(-1: 一直运行) 间隔时间(默认1秒)

```bash
# 发送测试事件
php vendor/bin/zwei/event-rabbitmq/run-send-test-event-message.php 事件key 运行次数(-1: 一直运行) 间隔时间(默认1秒)
php vendor/bin/zwei/event-rabbitmq/run-send-test-event-message.php event_demo_user_register_success -1 1 # 一直运行脚本,每秒发送一次事件
php vendor/bin/zwei/event-rabbitmq/run-send-test-event-message.php event_demo_user_register_success 2 0 # 运行2次脚本,连续发送事件

```

# Mongodb操作
```sql
db.getCollection("event_log").find({}).sort({"_id": -1});

# 查询"event_user_register"事件
db.getCollection("event_log").find({"eventKey": "event_user_register"}).pretty().sort({"_id": -1}).limit(100);
```

# 单元测试
```bash
# 发送Console事件
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php src/Tests/Queue/Services/GatewayServiceTest.php --filter testSendEventKeyEventConsole
# 发送非法事件
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php src/Tests/Queue/Services/GatewayServiceTest.php --filter testSendEventKeyEventNotFund
# 发送注册消息
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php src/Tests/Queue/Services/GatewayServiceTest.php --filter testSendEventKeyEventUserRegister
```