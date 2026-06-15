<?php

declare(strict_types=1);

namespace App\Notification;

interface NotificationServiceInterface
{
    public function send(string $message): void;
}