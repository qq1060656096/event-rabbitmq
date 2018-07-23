<?php
namespace Zwei\RabbitMqEvent\Cron;

use Zwei\RabbitMqEvent\Base\RabbitMqConfig;

/**
 * Class Cron
 * @package Zwei\RabbitMqEvent\Cron
 */
class Cron
{
    /**
     * 运行计划任务
     * @param string $cronName cron名
     * @return mixed
     */
    public final function run($cronName)
    {
        $cronInfo   = RabbitMqConfig::getCron($cronName);
        $class      = $cronInfo['class'];
        $callback   = $cronInfo['method'];
        // 处理cron
        $obj        = new $class();
        return call_user_func_array([$obj, $callback], []);
    }
}