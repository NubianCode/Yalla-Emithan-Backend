<?php

namespace App\Services;

use Kreait\Firebase\Messaging;
use Kreait\Firebase\Exception\MessagingException;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendNotification(string $token, $data): array
    {
        if ($token == null) {
            return ['success' => true];
        }

        $data['image'] = 'https://yalla-emtihan.com/yalla-emtihan/public/profile_images/' . $data['image'];
        $message = Messaging\CloudMessage::fromArray([
            'token' => $token,
            'notification' => [
                'title' => $data['title'],
                'body' => $data['body'],
                'image' => $data['image'],
                'sound' => 'default',
            ],
            'data' => $data,
            'apns' => [
                'payload' => [
                    'aps' => [
                        'contentAvailable' => false,
                    ],
                ],
                'headers' => [
                    'apns-push-type' => 'background',
                    'apns-priority' => '10',
                    'apns-topic' => 'io.flutter.plugins.firebase.messaging',
                ],
            ],
        ]);

        try {
            $this->messaging->send($message);
            return ['success' => true];
        } catch (MessagingException $e) {
            // Log the error for debugging purposes
            \Log::error('Firebase Notification Error: ' . $e->getMessage());
            return ['error' => 'Failed to send notification.', 'success' => false];
        }
    }

    public function sendToAll(string $topic, $data): array
    {
        $message = Messaging\CloudMessage::fromArray([
            'topic' => $topic, // Ensure users are subscribed to this topic
            'notification' => [
                'title' => $data['title'],
                'body' => $data['body'],
                'sound' => 'default',
            ],
            'data' => $data,
            'apns' => [
                'payload' => [
                    'aps' => [
                        'contentAvailable' => false,
                    ],
                ],
                'headers' => [
                    'apns-push-type' => 'background',
                    'apns-priority' => '10',
                    'apns-topic' => 'io.flutter.plugins.firebase.messaging',
                ],
            ],
        ]);

        try {
            $this->messaging->send($message);
            return ['success' => true];
        } catch (MessagingException $e) {
            \Log::error('Firebase Notification Error: ' . $e->getMessage());
            return ['error' => 'Failed to send notification to all.', 'success' => false];
        }
    }
}
