<?php

return [

    'host' => env('RABBITMQ_HOST', 'rabbitmq'),

    'port' => (int) env('RABBITMQ_PORT', 5672),

    'user' => env('RABBITMQ_USER', 'media_user'),

    'password' => env('RABBITMQ_PASSWORD', 'local-rabbit-password'),

    'queue' => 'file-uploaded',

];
