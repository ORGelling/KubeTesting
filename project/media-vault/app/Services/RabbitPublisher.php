<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitPublisher
{
    public function publishFileUploaded(int $fileId): void
    {
        $connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
        );

        $channel = $connection->channel();

        $channel->queue_declare(
            config('rabbitmq.queue'),
            false,
            true,
            false,
            false,
        );

        $message = new AMQPMessage(
            json_encode(['file_id' => $fileId], JSON_THROW_ON_ERROR),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ],
        );

        $channel->basic_publish(
            $message,
            '',
            config('rabbitmq.queue'),
        );

        $channel->close();
        $connection->close();
    }
}
