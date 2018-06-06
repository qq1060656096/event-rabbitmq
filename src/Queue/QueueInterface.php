<?php
namespace Zwei\EventRabbitMQ\Queue;

/**
 * 队列接口
 *
 * Interface QueueInterface
 * @package Zwei\EventRabbitMQ\Queue
 */
interface QueueInterface
{

    /**
     * 接受消息
     * @param string $queueKey 队列key
     */
    public function work($queueKey);

    /**
     * 消息处理
     *
     * @param \AMQPEnvelope $envelope
     * @param \AMQPQueue $queue
     */
    public function receive($envelope, $queue);
}