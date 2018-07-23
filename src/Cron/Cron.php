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
     * ���мƻ�����
     * @param string $cronName cron��
     * @return mixed
     */
    public final function run($cronName)
    {
        $cronInfo   = RabbitMqConfig::getCron($cronName);
        $class      = $cronInfo['class'];
        $callback   = $cronInfo['method'];
        // ����cron
        $obj        = new $class();
        return call_user_func_array([$obj, $callback], []);
    }
}