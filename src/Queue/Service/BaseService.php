<?php
namespace Zwei\RabbitMqEvent\Queue\Service;

use Zwei\RabbitMqEvent\Base\Helper;
use Zwei\RabbitMqEvent\Base\RabbitMqConfig;

/**
 * 服务基类
 * Class BaseService
 * @package Zwei\RabbitMqEvent\Queue
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
     * 版本号
     * @var string
     */
    protected $version = null;

    /**
     * 上次ping时间
     * @var int
     */
    protected $lastPingTime = 0;

        /**
         * 是否心跳
         *
         * @return bool
         */
    public function isPing()
    {
        $nowTime = time();
        // 心跳间隔时间
        $pingInteralTime = RabbitMqConfig::getCommon('rabbit_mq_ping_interval_time');
        if ($this->lastPingTime + $pingInteralTime < $nowTime) {
            $this->lastPingTime = $nowTime;
            return true;
        }
        return false;
    }

    /**
     * 保持心跳
     */
    public function ping()
    {
        // 保持心跳
        if ($this->isPing()) {
            Helper::pingMongo();
//            Helper::pingRedis();
        }
    }
}