<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
{
    $this->app->singleton(Messaging::class, function ($app) {
        $credentialsPath =  app()->environment('local') ? 'C:\Users\Moe\Documents\Nubian Code\Projects\Yalla Emtihan\code\yalla-emtihan-backend\yalla-emtihan\storage\firebase_credentials.json' : '/home/nubikkce/yalla-emtihan.com/backend/yalla-emtihan/storage/firebase_credentials.json';
        
        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException("Firebase credentials file not found at path: $credentialsPath");
        }

        $firebaseFactory = (new Factory)->withServiceAccount($credentialsPath);
        return $firebaseFactory->createMessaging();
    });
}

}
