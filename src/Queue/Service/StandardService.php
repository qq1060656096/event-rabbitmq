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
     * 事件使用队列key
     * @var string
     */
    protected $eventUseQueueKey = null;

    /**
     * 事件使用队列类型
     * @var string
     */
    protected $eventUseQueueType = null;

    /**
     * 事件使用队列配置
     * @var array
     */
    protected $eventUseQueueConfig = null;

    /**
     * 网管分发
     * @param string $queueKey 队列名
     */
    public function work($queueKey) {

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

        $consoleQueueConfig = RabbitMqConfig::getQueue('rabbit_queue_console');
        $this->queue->bind($this->exchangeName, $consoleQueueConfig['route_key']);// 绑定route_key

        $this->queue->consume([$this, 'receive']);
        $this->rabbtMq->disconnection();
    }

    /**
     * 消息处理
     *
     * @param \AMQPEnvelope $envelope
     * @param \AMQPQueue $queue
     */
    public function receive($envelope, $queue) {
        $nowTime = time();
        // 保持心跳
        $this->ping();

        $msgJson = $envelope->getBody();
        $msgJson = json_decode($msgJson, true);
        if ($msgJson['eventKey'] == 'Console') {
            $queue->ack($envelope->getDeliveryTag());
            switch ($msgJson['data']) {
                case 'reload':
                    echo sprintf("[date:%s]Event RabbitMQ: queue key '%s' reload.\n", date('Y-m-d H:i:s', $nowTime), $this->queueKey);
                    $this->rabbtMq->disconnection();
                    exit();
                    break;
                case 'ping':
                    echo sprintf("[date:%s]Event RabbitMQ: queue key '%s' ping.\n", date('Y-m-d H:i:s', $nowTime), $this->queueKey);
                    return;
            }
            return ;
        }

        // 消息转发到指定队列执行
        if ($this->queueConfig['forward']) {
            $msgJson['forward'] = $this->queueKey ;
            $msgJson['forwardLists'][] = $this->queueKey;// 转发列表，可能发生多次转发
            $forwardQueueConfig = RabbitMqConfig::getQueue($this->queueConfig['forward_queue_key']);
            // 消息转发
            $rabbitMq = new RabbitMq($this->exchangeName, $this->exchangeType);
            $rabbitMq->send($msgJson, $forwardQueueConfig['route_key']);
            $queue->ack($envelope->getDeliveryTag());
            return;
        }

        // 设置事件消费是的队列信息
        $this->eventUseQueueKey     = $this->queueKey;
        $this->eventUseQueueConfig  = $this->queueConfig;
        $this->eventUseQueueType    = $this->queueType;
        // 消息来自转发
        if (isset($msgJson['forward'])) {
            $this->eventUseQueueKey     = $msgJson['forwardLists'][0];
            $this->eventUseQueueConfig  = RabbitMqConfig::getQueue($this->eventUseQueueKey);
            $this->eventUseQueueType    = $this->eventUseQueueConfig['queue_type'];
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
                $this->updateAdditional($msgJson, $callbackResult, null);
            } else {
                $queue->ack($envelope->getDeliveryTag());
                $this->updateAdditional($msgJson, $callbackResult, null);
            }

        } catch (\Exception $e) { // 非法消息,直接确认
            echo $e;
            $queue->ack($envelope->getDeliveryTag());
            $callbackResult = new CallbackResult(Code::EXCEPTION, [], '异常');
            $this->updateAdditional($msgJson, $callbackResult, $e);
            return ;
        }

    }

    /**
     *
     * 广播消息(普通事件才会广播消息)
     *
     * @param array $message 消息内容
     * @return bool|null
     */
    public function broadcast(array $message) {
        // 普通队列, 广播消息
        $eventConfig = RabbitMqConfig::getEvent($message['eventKey']);
        switch (true) {
            case $this->queueType !== QueueType::STANDARD:// 不是普通事件不广播
                return null;
                break;
            case !$eventConfig['broadcast']:// 事件不广播
                return null;
                break;
        }

        // 广播消息前删除转发消息, 避免普通队列需要转发，然后监听队列处理消息时也需要转发
        unset($message['forward'], $message['forwardLists']);

        $rabbitMq = new RabbitMq($this->exchangeName, $this->exchangeType);
        $result = $rabbitMq->send($message, $message['eventKey'].'_success');
        return $result;
    }


    /**
     * 更新附加信息
     * @param array $message 消息内容
     * @param CallbackResult $callbackResult 返回结果对象
     * @param \Exception $e 异常
     * @return bool
     */
    public function updateAdditional(array $message, CallbackResult $callbackResult, \Exception $e = null) {
        $where = ['_id' => $message['_id']];
        switch ($this->eventUseQueueType) {
            case QueueType::LISTEN:// 监听队列
                $additional['listenQueueKey'] = $this->eventUseQueueKey;
                break;
            case QueueType::STANDARD:// 普通队列[标准队列]
                $additional['eventKey'] = $message['eventKey'];
                break;
            default:

                break;
        }
        switch (true) {
            case !empty($e):// 异常
                $exception = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'code' => $e->getCode(),
                    'traceString' => $e->getTraceAsString(),
                ];

                $additional['code']     = Code::EXCEPTION;
                $additional['message']  = '异常';
                $additional['data']     = [];
                $additional['error']    = $exception;
                break;
            default:
                $additional['code']     = $callbackResult->getCode();
                $additional['message']  = $callbackResult->getMessage();
                $additional['data']     = $callbackResult->getData();
                break;
        }

        switch ($this->eventUseQueueType) {
            case QueueType::LISTEN:// 监听队列
                $saveData = [
                    '$push' => [
                        'additional' => $additional
                    ]
                ];
                break;
            case QueueType::STANDARD:// 普通队列[标准队列]
                $status = $callbackResult->getCode() === Code::SUCCESS ? 1 : -1;
                $saveData = [
                    '$set' => ['status' => $status],
                    '$push' => [
                        'additional' => $additional
                    ]
                ];
                break;
            default:
                $saveData = [
                    '$push' => [
                        'additional' => $additional
                    ]
                ];
                break;
        }
        $collectionName = Helper::getCollectionName();
        $result = MongoDB::getInstance()->update($collectionName, $saveData, $where);
        // 成功才广播消息
        if ($callbackResult && $callbackResult->getCode() === Code::SUCCESS) {
            $message['additional'] = $additional;
            $this->broadcast($message);
        }

        return $result;
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
        switch ($this->eventUseQueueType) {
            case QueueType::LISTEN:// 监听队列
                $callback = $this->eventUseQueueConfig['callback'];
                list($class, $staticFunction) = explode('::', $callback);
                break;
            case QueueType::STANDARD:// 普通队列[标准队列]
                $eventConfig = RabbitMqConfig::getEvent($message['eventKey']);
                $callback = $eventConfig['callback'];
                list($class, $staticFunction) = explode('::', $callback);
                break;
            default:

                break;
        }
//        unset($message['additional']);
        return call_user_func($class.'::'. $staticFunction, $message);
    }
}