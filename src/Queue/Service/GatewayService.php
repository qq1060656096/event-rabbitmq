<?php
namespace Zwei\EventRabbitMQ\Queue\Service;

use Zwei\Base\Exception\ConfigException;
use Zwei\EventRabbitMQ\Base\MongoDB;
use Zwei\EventRabbitMQ\Base\RabbitMqConfig;
use Zwei\EventRabbitMQ\Base\Helper;
use Zwei\EventRabbitMQ\Base\RabbitMq;
use Zwei\EventRabbitMQ\Queue\QueueInterface;


/**
 * 网管服务分发
 *
 * Class GatewayService
 * @package Zwei\EventRabbitMQ\Queue
 */
class GatewayService extends BaseService implements QueueInterface
{
    /**
     * 网管分发
     * @param string $queueKey 队列名
     */
    public function work($queueKey)
    {
        // 重启队列
//        Helper::queueReload();
        $this->queueKey     = $queueKey;
        $rabbitMqConfig     = RabbitMqConfig::getCommon('rabbit_mq');
        $this->exchangeName = $rabbitMqConfig['exchange_name'];
        $this->exchangeType = AMQP_EX_TYPE_TOPIC;
        $this->queueConfig  = RabbitMqConfig::getQueue($queueKey);
        $this->version      = Helper::getVersion();

        $this->rabbtMq      = new RabbitMq($this->exchangeName, $this->exchangeType);
        $this->queue        = new \AMQPQueue($this->rabbtMq->getChannel());
        $this->queue->setName($queueKey);// 设置队列名
        $this->queue->setFlags(AMQP_DURABLE);// 设置队列持久化
        $this->queue->declareQueue();// 队列存在创建,否者就不创建
        $this->queue->bind($this->exchangeName, $this->queueConfig['route_key']);// 绑定route_key
        while (true) {
            $this->queue->consume([$this, 'receive']);
        }
    }

    /**
     * 消息处理
     *
     * @param \AMQPEnvelope $envelope
     * @param \AMQPQueue $queue
     */
    public function receive($envelope, $queue)
    {
        $nowTime = time();
        // 保持心跳
        self::ping();

        $msgJson = $envelope->getBody();
        $msgJson = json_decode($msgJson, true);
        if ($msgJson['eventKey'] == 'Console') {
            $queue->ack($envelope->getDeliveryTag());
            switch ($msgJson['data']) {
                case 'reload':
                    echo sprintf("[date:%s]Event RabbitMQ: gateway reload.\n", date('Y-m-d H:i:s', $nowTime));
                    $this->rabbtMq->disconnection();
                    exit();
                    break;
            }
            return ;
        }
        try {
            $eventConfig = RabbitMqConfig::getEvent($msgJson['eventKey']);
        } catch (ConfigException $e) { // 非法消息,直接确认
            echo sprintf("[date:%s]Event RabbitMQ: eventKey \"%s\" not found.\n", date('Y-m-d H:i:s', $nowTime), $msgJson['eventKey']);
            $queue->ack($envelope->getDeliveryTag());
            return ;
        }

        // 合法的消息持久化
        $eventData = [
            '_id'           => $msgJson['_id'],
            'status'        => 0,// 未处理状态
            'eventKey'      => $msgJson['eventKey'],
            'time'          => $nowTime,
            'ip'            => $msgJson['ip'],
            'version'       => $this->version,
            'data'          => $msgJson['data'],
            'additional'    => [],
        ];

        // 持久化到mongodb
        try {
            $collectionName = Helper::getCollectionName();
            MongoDB::getInstance()->insert($collectionName, $eventData);
        } catch (\Exception $e) {
            echo sprintf("[date:%s]Event RabbitMQ: %s.\n", date('Y-m-d H:i:s', $nowTime), $e->getMessage());
            $queue->ack($envelope->getDeliveryTag());
        }
        // 消息转发
        $queueConfig = RabbitMqConfig::getQueue($eventConfig['queue_key']);
        $rabbitMq = new RabbitMq($this->exchangeName, $this->exchangeType);
        $rabbitMq->send($eventData, $queueConfig['route_key']);
        $queue->ack($envelope->getDeliveryTag());
    }
}

//require_once dirname(dirname(dirname(__DIR__))).'/vendor/autoload.php';
//
//$queueKey = $argv[1];
//$service = new GatewayService();
//$service->work($queueKey);