<?php
namespace Zwei\RabbitMqEvent\Queue;

interface QueueCallbackResultInterface
{

    public function result($code, array $data, $msage);
}