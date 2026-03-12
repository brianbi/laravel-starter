<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DingTalkChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toDingTalk')) {
            return;
        }

        $message = $notification->toDingTalk($notifiable);

        if (empty($message)) {
            return;
        }

        $this->sendMessage($message);
    }

    /**
     * Send message to DingTalk webhook.
     */
    protected function sendMessage(array $message): void
    {
        $webhook = config('notification.channels.dingtalk.webhook');
        $secret = config('notification.channels.dingtalk.secret');

        if (empty($webhook)) {
            Log::warning('DingTalk webhook is not configured');
            return;
        }

        $url = $this->buildUrl($webhook, $secret);

        try {
            $response = Http::post($url, $message);

            if ($response->successful()) {
                $result = $response->json();
                if (($result['errcode'] ?? -1) !== 0) {
                    Log::error('DingTalk send failed', [
                        'errcode' => $result['errcode'],
                        'errmsg' => $result['errmsg'] ?? 'Unknown error',
                    ]);
                }
            } else {
                Log::error('DingTalk HTTP request failed', [
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('DingTalk send exception', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build webhook URL with signature.
     */
    protected function buildUrl(string $webhook, ?string $secret): string
    {
        if (empty($secret)) {
            return $webhook;
        }

        $timestamp = time() * 1000;
        $sign = $this->sign($timestamp, $secret);

        return $webhook . "&timestamp={$timestamp}&sign={$sign}";
    }

    /**
     * Generate signature.
     */
    protected function sign(int $timestamp, string $secret): string
    {
        $stringToSign = $timestamp . "\n" . $secret;
        $sign = hash_hmac('sha256', $stringToSign, $secret, true);

        return urlencode(base64_encode($sign));
    }

    /**
     * Create a text message.
     */
    public static function textMessage(string $content, array $atMobiles = [], bool $atAll = false): array
    {
        return [
            'msgtype' => 'text',
            'text' => [
                'content' => $content,
            ],
            'at' => [
                'atMobiles' => $atMobiles,
                'isAtAll' => $atAll,
            ],
        ];
    }

    /**
     * Create a markdown message.
     */
    public static function markdownMessage(string $title, string $text, array $atMobiles = [], bool $atAll = false): array
    {
        return [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => $title,
                'text' => $text,
            ],
            'at' => [
                'atMobiles' => $atMobiles,
                'isAtAll' => $atAll,
            ],
        ];
    }

    /**
     * Create an action card message.
     */
    public static function actionCardMessage(
        string $title,
        string $text,
        string $singleTitle = '',
        string $singleURL = ''
    ): array {
        return [
            'msgtype' => 'actionCard',
            'actionCard' => [
                'title' => $title,
                'text' => $text,
                'singleTitle' => $singleTitle,
                'singleURL' => $singleURL,
            ],
        ];
    }
}
