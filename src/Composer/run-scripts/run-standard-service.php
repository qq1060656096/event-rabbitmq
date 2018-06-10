<?php
include_once dirname(dirname(dirname(__DIR__))).'/autoload.php';

$queueKey = $argv[1];
$service = new \Zwei\RabbitMqEvent\Queue\Service\StandardService();
$service->work($queueKey);