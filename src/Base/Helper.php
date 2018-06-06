<?php
namespace Zwei\EventRabbitMQ\Base;


class Helper
{
    /**
     * 获取版本
     * @return string
     */
    public static function getVersion()
    {
        $version = '';
        return $version;
    }

    /**
     * 重启队列
     * @param array $routeKeys 路由key列表
     */
    public static function queueReload(array $routeKeys)
    {
        $event = [
            'eventKey'  => 'Console',
            'date'      => 'reload',
        ];
        $rabbitMqConfig = RabbitMqConfig::getCommon('rabbit_mq');
        $exchangeName   = $rabbitMqConfig['exchange_name'];
        $rabbitMq       = new RabbitMq($exchangeName);
        foreach ($routeKeys as $routeKey) {
            $rabbitMq->send($event, $routeKey);
        }
    }

    /**
     * 获取集合名
     * @return string
     */
    public static function getCollectionName()
    {
        $collectionName = RabbitMqConfig::getCommon('mongodb')['collection'];
        return $collectionName;
    }
}