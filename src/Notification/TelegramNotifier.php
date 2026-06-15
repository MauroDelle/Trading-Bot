<?php

declare(strict_types=1);

namespace App\Notification;

class TelegramNotifier implements NotificationServiceInterface
{
    public function __construct(
        private string $botToken,
        private string $chatId
    ) {}

    public function send(string $message): void
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            return; // Falla silenciosamente si no está configurado
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context  = stream_context_create($options);
        @file_get_contents($url, false, $context);
    }
}