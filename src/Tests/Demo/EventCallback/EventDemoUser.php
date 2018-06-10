<?php
namespace Zwei\RabbitMqEvent\Tests\Demo\EventCallback;

/**
 * 示例用户事件处理
 * Class EventDemoUserRegisterCallback
 * @package Zwei\RabbitMqEvent\Tests\Demo\EventCallback
 */
class EventDemoUser
{
    /**
     * 用户注册成功
     * @param array $message
     */
    public static function registerSuccess($message)
    {
        print_r($message);
        return true;
    }

    /**
     * 测试注册失败
     * @param array $message
     * @return bool
     */
    public static function testRegisterFail($message)
    {
        return false;
    }

    /**
     * 测试注册异常失败
     * @param array $message
     */
    public static function testRegisterException($message)
    {
        throw new \Exception("测试异常");
    }
}