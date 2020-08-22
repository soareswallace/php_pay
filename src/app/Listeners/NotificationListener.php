<?php

namespace App\Listeners;

use App\Events\MoneyExchangeEvent;
use App\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

class NotificationListener
{
    private const NOTIFICATION_URL = 'https://run.mocky.io/v3/b19f7b9f-9cbf-4fc6-ad22-dc30601aec04';
    private const MESSAGE_KEY = 'message';

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  MoneyExchangeEvent  $event
     * @return void
     */
    public function handle(MoneyExchangeEvent $event)
    {
        var_dump('Notificacao enviada');
    }

    /**
     * Sends the notification to the payee
     *
     * @param User $user
     */
    public function sendNotificationToTheUser(User $user)
    {
        $attempts = 1;

        while (!$this->hasBeenNotified() && $attempts <= 5) {
            $attempts++;
        }

        if ($attempts === 5) {
            //Throw a sentry
        }
    }

    /**
     * @return bool
     */
    private function hasBeenNotified()
    {
        $responseFromNotification = Http::get(self::NOTIFICATION_URL);
        return $responseFromNotification[self::MESSAGE_KEY] === 'Enviado';
    }
}
