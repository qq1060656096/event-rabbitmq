<?php
namespace Zwei\EventRabbitMQ\Queue;

interface QueueCallbackResultInterface
{

    public function result($code, array $data, $msage);
}