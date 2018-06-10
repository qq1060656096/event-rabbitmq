<?php
namespace Zwei\RabbitMqEvent\Base;


class Helper
{
    /**
     * 获取版本
     * @return string
     */
    public static function getVersion()
    {
        $version = '1.0.0.0';
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

    /**
     * 获取交换器名
     *
     * @return string
     */
    public static function getExchangeName()
    {
        $rabbitMqConfig = RabbitMqConfig::getCommon('rabbit_mq');
        $exchangeName   = $rabbitMqConfig['exchange_name'];
        return $exchangeName;
    }


    /**
     * mongoDB保持心跳
     */
    public static function pingMongo()
    {
        $dbName = MongoDB::getInstance()->getDbName();
        try {
            MongoDB::getInstance()->getDB()->selectDB($dbName);
        } catch (\Exception $e) {
            MongoDB::getInstance()->getDB()->connect();
            MongoDB::getInstance()->getDB()->selectDB($dbName);
        }
    }

    /**
     * Redis保持心跳
     */
    public static function pingRedis()
    {

    }
}