<?php
include_once dirname(dirname(dirname(__DIR__))).'/autoload.php';

$queueKey = "rabbit_queue_gateway";
$service = new \Zwei\RabbitMqEvent\Queue\Service\GatewayService();
$service->work($queueKey);