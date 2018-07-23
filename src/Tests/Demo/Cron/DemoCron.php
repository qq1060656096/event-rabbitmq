<?php
namespace Zwei\RabbitMqEvent\Tests\Demo\Cron;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/23
 * Time: 10:00
 */
class DemoCron
{
    public function testFunc() {
        $i = 1;
        echo "run cron\n";
        while (true) {
            echo $i++, "\n";
            sleep(1);
        }
    }
}