<?php
namespace Zwei\EventRabbitMQ\Queue\Service;

use Zwei\EventRabbitMQ\Base\Helper;

/**
 * 服务基类
 * Class BaseService
 * @package Zwei\EventRabbitMQ\Queue
 */
class BaseService
{
    /**
     * 交换器名称
     * @var string
     */
    protected $exchangeName = null;

    /**
     * 交换器类型
     * @var string
     */
    protected $exchangeType = null;

    /**
     * @var RabbitMq
     */
    protected $rabbtMq = null;

    /**
     * 队列
     * @var \AMQPQueue
     */
    protected $queue = null;

    /**
     * 队列key
     * @var string
     */
    protected $queueKey = null;

    /**
     * 队列配置
     * @var array
     */
    protected $queueConfig = null;

    /**
     * 版本号
     * @var string
     */
    protected $version = null;

    /**
     * 保持心跳
     */
    public static function ping()
    {
        // 保持心跳
        if (Helper::isPing()) {
            Helper::pingMongo();
            Helper::pingRedis();
        }
    }
}