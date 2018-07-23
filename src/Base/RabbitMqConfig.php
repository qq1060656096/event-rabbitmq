<?php
namespace Zwei\RabbitMqEvent\Base;

use Zwei\Base\Config;

/**
 * 获取配置
 *
 * Class RabbitMqConfig
 * @package Zwei\RabbitMqEvent\Base
 */
class RabbitMqConfig extends Config
{
    /**
     * 获取公共配置
     *
     * @param string $name
     * @return mixed
     */
    public static function getCommon($name)
    {
        return parent::get($name, 'zwei-rabbitmq-event-base', 'rabbitmq-event.config.yml');
    }

    /**
     * 获取指定事件配置
     *
     * @param string $name
     * @return mixed
     */
    public static function getEvent($name)
    {
        return parent::get($name, 'zwei-rabbitmq-event-events', 'events.config.yml');
    }

    /**
     * 获取指定队列配置
     * @param string $name
     * @return mixed
     */
    public static function getQueue($name)
    {
        return parent::get($name, 'zwei-rabbitmq-event-queues', 'queues.config.yml');
    }

    /**
     * 获取cron
     * @param string $name
     * @return mixed
     */
    public static function getCron($name)
    {
        return parent::get($name, 'zwei-rabbitmq-event-crons', 'crons.config.yml');
    }
}
