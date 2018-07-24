<?php
namespace Zwei\RabbitMqEvent\Queue;

/**
 * 队列类型
 *
 * Class QueueType
 * @package Zwei\RabbitMqEvent\Queue
 */
class QueueType
{
    /**
     * 标准队列
     */
    const STANDARD = 'standard';

    /**
     * 监听队列
     */
    const LISTEN = 'listen';
}