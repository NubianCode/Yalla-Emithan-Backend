<?php

namespace App\Http\Controllers;

use App\Services\FirebaseNotificationService;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;

class Notification extends Controller
{
    protected $firebaseService;
    protected $httpClient;

    public function __construct()
    {
        $this->firebaseService = app(FirebaseNotificationService::class);;
        $this->httpClient = new HttpClient();
    }

    public function requestSocket($data, $link)
    {
        $url = app()->environment('local')
            ? "http://localhost:3000/socket/{$link}"
            : "https://socket.yalla-emtihan.com/socket/{$link}";

        return $this->httpClient->post($url, [
            'form_params' => [
                'data' => $data,
                'link' => $link,
            ]
        ]);
    }

    public function sendSinglFullAppNotification($token, $event, $data)
    {
        $this->requestSocket($data, $event);

        return $this->firebaseService->sendNotification($token, $data);
    }

    public function sendGroupFullAppNotification($topic, $event, $data)
    {
        $this->requestSocket($data, $event);

        return $this->firebaseService->sendToAll($topic, $data);
    }

    private function formatPhoneNumber($phoneNumber)
    {
        $clean = ltrim($phoneNumber, '+');
        return str_ends_with($clean, '@c.us') ? $clean : $clean . '@c.us';
    }
}
