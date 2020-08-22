<?php

namespace App\Providers;

use App\Events\MoneyExchangeEvent;
use App\Listeners\NotificationListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\MoneyExchangeEvent' => [
            'App\Listeners\NotificationListener'
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Event::listen(MoneyExchangeEvent::MONEY_EXCHANGE_EVENT, function (MoneyExchangeEvent $moneyExchangeEvent) {
            $listener = new NotificationListener();
            $listener->sendNotificationToTheUser($moneyExchangeEvent->getPayee());
        });
    }
}
