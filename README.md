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
    "eventKey":"event_user_register",
    "ip":"127.0.0.1",
    "data":{
        "phone":"15412345678"
    }
}
```

# Mongodb操作
```sql
db.getCollection("event_log").find({}).sort({"_id": -1});
```
