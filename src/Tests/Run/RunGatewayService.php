<?php
$file = dirname(dirname(dirname(__DIR__))).'/vendor/autoload.php';
include_once $file;

$queueKey = "rabbit_event_queue_gateway";
$service = new \Zwei\EventRabbitMQ\Queue\Service\GatewayService();
$service->work($queueKey);