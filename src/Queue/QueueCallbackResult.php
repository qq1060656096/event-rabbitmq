<?php
namespace Zwei\EventRabbitMQ\Queue;

/**
 * 队列返回结果类
 * 
 * Class QueueCallbackResult
 * @package Zwei\EventRabbitMQ\Queue
 */
class QueueCallbackResult
{
    /**
     * 队列格式化返回结果
     *
     * @param $code
     * @param $message
     * @param $data
     * @return array
     */
    public static function result($code, $message, $data)
    {
        return [
            'code' => $code,
            'msg' => $message,
            'data' => $data,
        ];
    }

}