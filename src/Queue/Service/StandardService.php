<?php
namespace Zwei\EventRabbitMQ\Queue\Service;

use Zwei\EventRabbitMQ\Base\Helper;
use Zwei\EventRabbitMQ\Base\MongoDB;
use Zwei\EventRabbitMQ\Base\RabbitMq;
use Zwei\EventRabbitMQ\Base\RabbitMqConfig;

/**
 * 标准服务处理消息
 *
 * Class StandardService
 * @package Zwei\EventRabbitMQ\Queue\Service
 */
class StandardService extends BaseService  implements QueueInterface
{
    /**
     * 是不是监听队列
     *
     * @var bool
     */
    protected $listenQueue = false;

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
        $this->queueConfig  = RabbitMqConfig::getQueue($this->queueKey);
        $this->version      = Helper::getVersion();

        $this->rabbtMq      = new RabbitMq($this->exchangeName, $this->exchangeType);
        $this->queue        = new \AMQPQueue($this->rabbtMq->getChannel());
        $this->queue->setName($this->queueKey);// 设置队列名
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
        $this->ping();

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

        // 消息转发到指定队列执行
        if ($this->queueConfig['forward']) {
            $msgJson['forward'] = $this->queueKey ;
            $forwardQueueConfig = RabbitMqConfig::getQueue($this->queueConfig['forward_queue_key']);
            // 消息转发
            $rabbitMq = new RabbitMq($this->exchangeName, $this->exchangeType);
            $rabbitMq->send($msgJson, $forwardQueueConfig['route_key']);
            $queue->ack($envelope->getDeliveryTag());
            return;
        }

        // 消息版本不一致, 队列重启
        if ($this->version != $msgJson['version']) {
            echo sprintf("[date:%][message-version: %s][queue-version: %]The message version is different from the queue version \n", date('Y-m-d H:i:s', $nowTime));
            $queue->ack($envelope->getDeliveryTag());
            exit();
        }
        
        try {
            if ($result = $this->callback($msgJson)) {
                $queue->ack($envelope->getDeliveryTag());
                $this->receiveSuccess($msgJson, $result);
            } else {
                $queue->ack($envelope->getDeliveryTag());
                $this->receiveFail($msgJson);
            }
        } catch (\Exception $e) { // 非法消息,直接确认
            $queue->ack($envelope->getDeliveryTag());
            $this->receiveException($msgJson, $e);
            return ;
        }



    }


    /**
     * 回调成功
     * @param array $message 消息
     * @param array $additional 附加消息
     */
    public function callbackSuccess(array $message, array $additional)
    {

        $where = ['_id' => $message['_id']];
        // 监听队列
        if ($this->listenQueue) {
            $additional = [
                'listenQueueKey'    => $this->queueKey,
            ];
        } else {
            $additional = [
                'eventKey'    => $message['eventKey'],
            ];
        }
        // 普通队列
        $saveData = [
            '$set' => ['status' => 1],
            '$push' => [
                'additional' => $additional
            ]
        ];
        $collectionName = Helper::getCollection();
        MongoDB::getInstance()->update($collectionName, $saveData, $where);

        // 普通队列, 广播消息
        $eventConfig = RabbitMqConfig::getEvent($message['eventKey']);
        if (!$this->listenQueue && $eventConfig['broadcast']) {
            $rabbitMq = new RabbitMq($this->exchangeName, $this->exchangeType);
            $rabbitMq->send($message, $message['eventKey'].'_success');
        }
        return;
    }

    /**
     * 回调失败
     *
     * @param array $message 消息
     * @param array $additional 附加消息
     */
    public function callbackFail(array $message, array $additional)
    {
        $where = ['_id' => $message['_id']];
        // 监听队列
        if ($this->listenQueue) {
            $additional = [
                'listenQueueKey'    => $this->queueKey,
            ];
        } else {
            $additional = [
                'eventKey'    => $message['eventKey'],
            ];
        }
        // 普通队列
        $saveData = [
            '$set' => ['status' => -1],
            '$push' => [
                'additional' => $additional
            ]
        ];
        $collectionName = Helper::getCollection();
        MongoDB::getInstance()->update($collectionName, $saveData, $where);
        return;
    }

    /**
     * 回调函数异常
     * @param array $message
     * @param \Exception $e
     */
    public function callbackException(array $message, \Exception $e)
    {
        $exception = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
            'traceString' => $e->getTraceAsString(),
        ];

        $where = ['_id' => $message['_id']];
        // 监听队列
        if ($this->listenQueue) {
            $additional = [
                'listenQueueKey'    => $this->queueKey,
                'error' => $exception,
            ];
            $saveData = [
                '$push' => [
                    'additional' => $additional
                ]
            ];
            $collectionName = Helper::getCollection();
            MongoDB::getInstance()->update($collectionName, $saveData, $where);
            return;
        }
        // 普通队列
        $saveData = [
            '$set' => ['status' => -1],
            '$push' => [
                'error' => $exception
            ]
        ];
        $collectionName = Helper::getCollection();
        MongoDB::getInstance()->update($collectionName, $saveData, $where);
        return;
    }

    /**
     * @param array $message
     * @return mixed
     */
    public function callback(array $message)
    {
        $eventConfig = RabbitMqConfig::getEvent($message['eventKey']);
        $callback = $eventConfig['callback'];
        list($class, $staticFunction) = explode('.', $callback);
        return call_user_func($class.'::'. $staticFunction, [$message]);
    }
}