<?php
namespace Zwei\EventRabbitMQ\Gateway;

use Zwei\Base\Exception\ConfigException;
use Zwei\EventRabbitMQ\Base\Helper;
use Zwei\EventRabbitMQ\Base\MongoDB;
use Zwei\EventRabbitMQ\Base\RabbitMq;
use Zwei\EventRabbitMQ\Base\RabbitMqConfig;

/**
 * 网管服务
 *
 * Class Service
 * @package Zwei\EventRabbitMQ\Gateway
 */
class Service
{
    /**
     * 交换器名称
     * @var string
     */
    private static $exchangeName = null;

    /**
     * 交换器类型
     * @var string
     */
    private static $exchangeType = null;

    /**
     * @var RabbitMq
     */
    private static $rabbtMq = null;

    /**
     * 队列
     * @var \AMQPQueue
     */
    private static $queue = null;

    /**
     * 队列配置
     * @var array
     */
    private static $queueConfig = null;

    /**
     * 版本号
     * @var string
     */
    private static $version = null;

    /**
     * 网管分发
     */
    /**
     * @param string $queueKey 队列名
     */
    public static function distribute($queueKey)
    {
        // 重启队列
//        Helper::queueReload();

        $rabbitMqConfig     = RabbitMqConfig::getCommon('rabbit_mq');
        self::$exchangeName = $rabbitMqConfig['exchange_name'];
        self::$exchangeType = AMQP_EX_TYPE_TOPIC;
        self::$queueConfig  = RabbitMqConfig::getQueue($queueKey);
        self::$version      = Helper::getVersion();

        self::$rabbtMq      = new RabbitMq(self::$exchangeName, self::$exchangeType);
        self::$queue        = new \AMQPQueue(self::$rabbtMq->getChannel());
        self::$queue->setName('route_gateway');// 设置队列名
        self::$queue->setFlags(AMQP_DURABLE);// 设置队列持久化
        self::$queue->declareQueue();// 队列存在创建,否者就不创建
        while (true) {
            self::$queue->consume(__CLASS__.'::receive');
        }
    }

    /**
     * 消息处理
     *
     * @param \AMQPEnvelope $envelope
     * @param \AMQPQueue $queue
     */
    public static function receive($envelope, $queue)
    {
        $nowTime = time();
        $msgJson = $envelope->getBody();
        $msgJson = json_decode($msgJson, true);
        if ($msgJson['eventKey'] == 'Console') {
            $queue->ack($envelope->getDeliveryTag());
            switch ($msgJson['data']) {
                case 'reload':
                    echo sprintf("[date:%s]Event RabbitMQ: gateway reload.\n", date('Y-m-d H:i:s', $nowTime));
                    self::$rabbtMq->disconnection();
                    exit();
                    break;
            }
            return ;
        }
        try {
            $eventConfig = RabbitMqConfig::getEvent($msgJson['eventKey']);
        } catch (ConfigException $e) { // 非法消息,直接确认
            echo sprintf("[date:%s]Event RabbitMQ: eventKey not found.\n", date('Y-m-d H:i:s', $nowTime));
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
            'version'       => self::$version,
            'data'          => $msgJson['data'],
            'additional'    => [],
        ];

        // 持久化到mongodb
        try {
            $collectionName = Helper::getCollection();
            MongoDB::getInstance()->insert($collectionName, $eventData);
        } catch (\Exception $e) {
            echo sprintf("[date:%s]Event RabbitMQ: %s.\n", date('Y-m-d H:i:s', $nowTime), $e->getMessage());
            $queue->ack($envelope->getDeliveryTag());
        }
        // 消息转发
        $queueConfig = RabbitMqConfig::getQueue($eventConfig['queue_key']);
        $rabbitMq = new RabbitMq(self::$exchangeName, self::$exchangeType);
        $rabbitMq->send($eventData, $queueConfig['route_key']);
        $queue->ack($envelope->getDeliveryTag());
    }
}

require_once dirname(dirname(__DIR__)).'/vendor/autoload.php';

$queueKey = $argv[1];
Service::distribute($queueKey);