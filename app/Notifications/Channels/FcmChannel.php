<?php

namespace App\Notifications\Channels;

use App\Models\DeviceToken;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FcmChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $tokens = $notifiable->deviceTokens()->pluck('token');
        if ($tokens->isEmpty()) {
            return;
        }

        $payload = $notification->toFcm($notifiable);
        $message = CloudMessage::new()
            ->withNotification(FcmNotification::create($payload['title'], $payload['body']))
            ->withData($payload['data'] ?? []);

        $report = Firebase::messaging()->sendMulticast($message, $tokens->all());

        foreach ($report->invalidTokens() as $invalidToken) {
            DeviceToken::where('token', $invalidToken)->delete();
        }
    }
}
