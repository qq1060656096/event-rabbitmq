<?php
namespace Zwei\RabbitMqEvent\Queue\Service;

use Zwei\RabbitMqEvent\Base\Helper;
use Zwei\RabbitMqEvent\Base\MongoDB;
use Zwei\RabbitMqEvent\Base\RabbitMq;
use Zwei\RabbitMqEvent\Base\RabbitMqConfig;
use Zwei\RabbitMqEvent\Queue\CallbackResult;
use Zwei\RabbitMqEvent\Queue\Code;
use Zwei\RabbitMqEvent\Queue\QueueInterface;
use Zwei\RabbitMqEvent\Queue\QueueType;

/**
 * 标准服务处理消息
 *
 * Class StandardService
 * @package Zwei\RabbitMqEvent\Queue\Service
 */
class StandardService extends BaseService  implements QueueInterface
{
    /**
     * 队列类型
     *
     * @var string
     *
     * @see QueueType
     * @see QueueType::STANDARD
     * @see QueueType::LISTEN
     *
     */
    protected $queueType = '';

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
        $this->queueType    = $this->queueConfig['queue_type'];
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
        $this->rabbtMq->disconnection();
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
//        $this->ping();

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
            $callbackResult = $this->callback($msgJson);
            if ($callbackResult->getCode() === Code::SUCCESS) {
                $queue->ack($envelope->getDeliveryTag());
                $this->callbackSuccess($msgJson, $callbackResult);
            } else {
                $queue->ack($envelope->getDeliveryTag());
                $this->callbackFail($callbackResult);
            }
        } catch (\Exception $e) { // 非法消息,直接确认
            echo $e;
            $queue->ack($envelope->getDeliveryTag());
            $this->callbackException($msgJson, $e);
            return ;
        }



    }


    /**
     * 回调成功
     * @param array $message 消息
     * @param CallbackResult $callbackResult 消息返回结果
     */
    public function callbackSuccess(array $message, CallbackResult $callbackResult)
    {

        $where = ['_id' => $message['_id']];
        $additional = [
            'code' => $callbackResult->getCode(),
            'data' => $callbackResult->getData(),
            'message' => $callbackResult->getMessage()
        ];
        switch ($this->queueType) {
            case QueueType::LISTEN:// 监听队列
                $additional['listenQueueKey'] = $this->queueKey;
                break;
            case QueueType::STANDARD:// 普通队列[标准队列]
            default:
                $additional['eventKey'] = $message['eventKey'];
                break;
        }
        // 普通队列
        $saveData = [
            '$set' => ['status' => 1],
            '$push' => [
                'additional' => $additional
            ]
        ];
        $collectionName = Helper::getCollectionName();
        MongoDB::getInstance()->update($collectionName, $saveData, $where);

        // 普通队列, 广播消息
        $eventConfig = RabbitMqConfig::getEvent($message['eventKey']);
        if ($this->queueType === QueueType::STANDARD && $eventConfig['broadcast']) {
            $rabbitMq = new RabbitMq($this->exchangeName, $this->exchangeType);
            $rabbitMq->send($message, $message['eventKey'].'_success');
        }
        return;
    }

    /**
     * 回调失败
     *
     * @param array $message 消息
     * @param CallbackResult $callbackResult 消息返回结果
     */
    public function callbackFail(array $message, CallbackResult $callbackResult)
    {
        $where = ['_id' => $message['_id']];
        $additional = [
            'code' => $callbackResult->getCode(),
            'data' => $callbackResult->getData(),
            'message' => $callbackResult->getMessage()
        ];
        switch ($this->queueType) {
            case QueueType::LISTEN:// 监听队列
                $additional['listenQueueKey'] = $this->queueKey;
                break;
            case QueueType::STANDARD:// 普通队列[标准队列]
            default:
                $additional['eventKey'] = $message['eventKey'];
                break;
        }
        // 普通队列
        $saveData = [
            '$set' => ['status' => -1],
            '$push' => [
                'additional' => $additional
            ]
        ];
        $collectionName = Helper::getCollectionName();
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
        $additional = [
            'code' => Code::FAILURE,
            'data' => [],
            'message' => "",
            'error' => $exception,
        ];
        switch ($this->queueType) {
            case QueueType::LISTEN:// 监听队列
                $additional['listenQueueKey'] = $this->queueKey;
                break;
            case QueueType::STANDARD:// 普通队列[标准队列]
            default:
                $additional['eventKey'] = $message['eventKey'];
                break;
        }

        $saveData = [
            '$set' => ['status' => -1],
            '$push' => [
                'additional' => $additional
            ]
        ];
        $collectionName = Helper::getCollectionName();
        MongoDB::getInstance()->update($collectionName, $saveData, $where);
        return;
    }

    /**
     *
     * 调用回调方法
     *
     * @param array $message
     * @return CallbackResult
     */
    public function callback(array $message)
    {
        $eventConfig = RabbitMqConfig::getEvent($message['eventKey']);
        $callback = $eventConfig['callback'];
        list($class, $staticFunction) = explode('::', $callback);
        unset($message['additional']);
        return call_user_func($class.'::'. $staticFunction, $message);
    }
}