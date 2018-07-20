<?php
namespace Zwei\RabbitMqEvent\Composer;

use Composer\Script\Event;

/**
 * composer 脚本
 * Class Scripts
 * @package Zwei\RabbitMqEvent\Composer
 */
class Scripts
{
    /**
     * 在资源包安装后触发
     * @param Event $event
     */
    public static function postPackageInstall(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $binDir = $config->get("bin-dir");
        self::initScript($binDir);

    }


    /**
     * 在资源包更新后触发
     * @param Event $event
     */
    public static function postPackageUpdate(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $binDir = $config->get("bin-dir");
        self::initScript($binDir);
    }


    /**
     * 初始化运行脚本
     * @param $binDir
     */
    public static function initScript($binDir)
    {
        self::copyScript($binDir, "run-gateway-service.php");
        self::copyScript($binDir, "run-send-test-event-message.php");
        self::copyScript($binDir, "run-standard-service.php ");
    }

    /**
     * 复制脚本
     * @param $binDir
     * @param $fileName
     */
    public static function copyScript($binDir, $fileName)
    {
        $binDir         = $binDir."/zwei/rabbitmq-event";
        @mkdir($binDir, null , true);
        $originFilePath = __DIR__.'/run-scripts/'.$fileName;
        $tartFilePath   = $binDir.'/'.$fileName;
        copy($originFilePath, $tartFilePath);
    }
}