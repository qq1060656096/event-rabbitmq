<?php
namespace Zwei\RabbitMqEvent\Base;

/**
 * 事件返回结果
 *
 * Class EventResult
 * @package Zwei\RabbitMqEvent\Base
 */
class EventResult
{
    /**
     * 事件返回结果
     *
     * @param string $code 消息码
     * @param string $msg 消息
     * @param mixed $data 数据
     * @return array
     */
    public static function returnResult($code, $msg, $data)
    {
        return [
            'code'  => $code,
            'msg'   => $msg,
            'data'  => $data,
        ];
    }
}