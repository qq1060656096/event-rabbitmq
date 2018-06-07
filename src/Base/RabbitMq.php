<?php
namespace Zwei\EventRabbitMQ\Base;

use Zwei\EventRabbitMQ\Exception\EventMqRuntimeException;

/**
 * Class RabbitMq
 * @package Zwei\EventRabbitMQ\Base
 */
class RabbitMq
{
    /**
     * @var \AMQPConnection
     */
    private $connection = null;

    /**
     * @var \AMQPChanne
     */
    private $channel = null;

    /**
     * 构造方法初始化
     * RabbitMq constructor.
     * @param string $exchangeName 交换器名
     * @param string $exchangeType 交换器类型
     * @throws EventMqRuntimeException
     */
    public function __construct($exchangeName, $exchangeType = AMQP_EX_TYPE_TOPIC)
    {
        $rabbitMqConfig = RabbitMqConfig::getCommon('rabbit_mq');
        $credentials = [
            'host'  => $rabbitMqConfig['host'],
            'port'  => $rabbitMqConfig['port'],
            'vhost' => $rabbitMqConfig['vhost'],
            'login' => $rabbitMqConfig['user'],
            'password' => $rabbitMqConfig['pass'],
        ];

        $this->connection = new \AMQPConnection($credentials);

        if (!$this->connection->connect()) {
            throw new EventMqRuntimeException("Cannot connect to rabbit server.");
        }

        // 创建通道
        $this->channel = new \AMQPChannel($this->connection);
        $this->channel->qos(0, 1);

        $this->exhange = new \AMQPExchange($this->channel);
        $this->exhange->setName($exchangeName);
        $this->exhange->setType($exchangeType);
        $this->exhange->setFlags(AMQP_DURABLE);// 设置持久化
        $this->exhange->declareExchange();

    }

    /**
     * 关闭连接
     */
    public function disconnection()
    {
        $this->connection->disconnect();
    }

    /**
     * @return \AMQPChanne|\AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return \AMQPExchange
     */
    public function getExchange()
    {
        return $this->exhange;
    }

    /**
     * 发送消息
     * @param array $message 消息内容
     * @param string $routeKey 路由key
     * @return bool
     */
    public function send(array $message, $routeKey)
    {
        $message = json_encode($message);
        return $this->exhange->publish($message, $routeKey);
    }
}
