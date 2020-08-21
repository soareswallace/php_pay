<?php

namespace App\Events;

use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MoneyExchangeEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    const EVENT_NAME = 'money_exchange_event';

    /** @var User */
    private $payee;

    /** @var User */
    private $payer;

    /** @var float */
    private $value;

    /**
     * MoneyExchangeEvent constructor.
     * @param User $payer
     * @param User $payee
     * @param float $value
     */
    public function __construct(User $payer, User $payee, float $value)
    {
        $this->payer = $payer;
        $this->payee = $payee;
        $this->value = $value;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

    /**
     * @return User
     */
    public function getPayee()
    {
        return $this->payee;
    }
}
