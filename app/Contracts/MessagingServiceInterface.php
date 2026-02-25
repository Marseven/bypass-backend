<?php

namespace App\Contracts;

interface MessagingServiceInterface
{
    public function sendTextMessage($to, $body): array;

    public function sendBulkMessages($messages): array;

    public function formatPhoneNumber($phone): string;
}
