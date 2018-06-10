<?php
namespace Zwei\RabbitMqEvent\Tests\Queue\Services;

use PHPUnit\Framework\TestCase;
use Zwei\RabbitMqEvent\Base\RabbitMq;
use Zwei\RabbitMqEvent\Base\RabbitMqConfig;
use Zwei\RabbitMqEvent\Queue\Service\GatewayService;

/**
 * 网管单元测试
 *
 * Class GatewayServiceTest
 * @package Zwei\RabbitMqEvent\Tests\Queue\Services
 */
class GatewayServiceTest extends TestCase
{
    /**
     * 发送Console事件
     */
    public function testSendEventKeyEventConsole()
    {
        $rabbitMqConfig = RabbitMqConfig::getCommon('rabbit_mq');
        $exchangeName   = $rabbitMqConfig['exchange_name'];
        $rabbitmq       = new RabbitMq($exchangeName);
        $_id = date('Ymd.His.');
        $eventData = [
            "_id"       => "{$_id}-127_0_0_1-1000",
            "eventKey"  => "Console",
            "ip"        => "127.0.0.1",
            "data"      =>  "reload",
        ];
        $rabbitmq->send($eventData, 'route_gateway');
    }

    /**
     * 发送非法事件
     */
    public function testSendEventKeyEventNotFund()
    {
        $rabbitMqConfig = RabbitMqConfig::getCommon('rabbit_mq');
        $exchangeName   = $rabbitMqConfig['exchange_name'];
        $rabbitmq       = new RabbitMq($exchangeName);
        $_id = date('Ymd.His.');
        $eventData = [
            "_id"       => "{$_id}-127_0_0_1-1000",
            "eventKey"  => "EventNotFund-{$_id}",
            "ip"        => "127.0.0.1",
            "data"      =>  "reload",
        ];
        $rabbitmq->send($eventData, 'route_gateway');
    }

    /**
     * 发送event_user_register事件
     */
    public function testSendEventKeyEventUserRegister()
    {
        $rabbitMqConfig = RabbitMqConfig::getCommon('rabbit_mq');
        $exchangeName   = $rabbitMqConfig['exchange_name'];
        $rabbitmq       = new RabbitMq($exchangeName);
        $_id = date('Ymd.His.');
        $eventData = [
            "_id"       => "{$_id}-127_0_0_1-1000",
            "eventKey"  => "event_user_register",
            "ip"        => "127.0.0.1",
            "data"      =>  ["phone" => "15412345678"],
        ];
        $rabbitmq->send($eventData, 'route_gateway');
    }
}