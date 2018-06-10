<?php
namespace Zwei\RabbitMqEvent\Base;

/**
 * 事件消息
 *
 * Class EventMessage
 * @package Zwei\RabbitMqEvent\Base
 */
class EventMessage
{
    /**
     * 发送事件到网管
     * @param string $eventKey 事件key
     * @param array $data 数据
     * @param string $ip ip地址
     * @return string
     */
    public static function send($eventKey, array $data, $ip = "0.0.0.0")
    {

        $id = self::getId($ip);
        $ventData = [
            '_id'       => $id,
            'eventKey'  => $eventKey,
            'data'      => $data,
            'ip'        => $ip,
        ];
        $exchangeName   = Helper::getExchangeName();
        $rabbitMq       = new RabbitMq($exchangeName);
        $routeKey       = "route_gateway";
        $rabbitMq->send($ventData, $routeKey);
        return $id;
    }

    /**
     * 获取事件id
     * @param $ip
     * @return string
     */
    public static function getId($ip)
    {
        static $count;
        $count ++;
        $idArr = [
            time(),
            $ip,
            getmypid(),
            $count
        ];
        $id = implode('-', $idArr);
        return $id;
    }
}