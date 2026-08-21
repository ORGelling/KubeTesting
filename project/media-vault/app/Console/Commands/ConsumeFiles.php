<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeFiles extends Command
{
    protected $signature = 'files:consume';

    protected $description = 'Consume uploaded file messages from RabbitMQ';

    public function handle(): int
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

        $channel->basic_consume(
            config('rabbitmq.queue'),
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message): void {
                $payload = json_decode($message->getBody(), true);

                $mediaFile = MediaFile::find($payload['file_id'] ?? null);

                if ($mediaFile) {
                    // This is the deliberately simple "background job".
                    // Later, this is where virus scanning, image resizing,
                    // document parsing, or video processing could go.
                    $mediaFile->update([
                        'status' => 'complete',
                    ]);
                }

                $message->ack();
            },
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        return self::SUCCESS;
    }
}
