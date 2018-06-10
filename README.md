# Event RabbitMQ

[查看大图](https://raw.githubusercontent.com/qq1060656096/event-rabbitmq/master/dev/images/event-rabbmitmq.png)
![Event RabbitMQ流程图](dev/images/event-rabbmitmq.small.png?dev/images/event-rabbmitmq.png)

# 启动队列
```bash
# 启动网关队列
php Service.php rabbit_queue_gateway
```


# 测试网关事件
```json
{
    "_id":"201718060501-127_0_0_1-1000",
    "eventKey":"event_demo_user_register",
    "ip":"127.0.0.1",
    "data":{
        "phone":"15412345678"
    }
}
```

# Mongodb操作
```sql
db.getCollection("event_log").find({}).sort({"_id": -1});

# 查询"event_user_register"事件
db.getCollection("event_log").find({"eventKey": "event_user_register"}).pretty().sort({"_id": -1}).limit(100);
```

# 单元测试
```bash
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php src/Tests/Queue/Services/GatewayServiceTest.php --filter testWork

# 网管测试
php vendor/bin/zwei/event-rabbitmq/run-gateway-service.php

# 普通队列
php vendor/bin/zwei/event-rabbitmq/run-standard-service.php rabbit_queue_single
{
    "_id":"201718060501-127_0_0_1-1001",
    "eventKey":"event_demo_user_register",
    "ip":"127.0.0.1",
    "data":{
        "phone":"15412345678"
    }
}
# 发送测试事件
php vendor/bin/zwei/event-rabbitmq/run-send-test-event-message.php 事件key 运行次数(-1: 一直运行) 间隔时间(默认1秒)
php vendor/bin/zwei/event-rabbitmq/run-send-test-event-message.php event_demo_user_register_success -1 1 # 一直运行脚本,每秒发送一次事件
php vendor/bin/zwei/event-rabbitmq/run-send-test-event-message.php event_demo_user_register_success 2 0 # 运行2次脚本,连续发送事件

# 发送Console事件
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php src/Tests/Queue/Services/GatewayServiceTest.php --filter testSendEventKeyEventConsole
# 发送非法事件
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php src/Tests/Queue/Services/GatewayServiceTest.php --filter testSendEventKeyEventNotFund
# 发送注册消息
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php src/Tests/Queue/Services/GatewayServiceTest.php --filter testSendEventKeyEventUserRegister
```