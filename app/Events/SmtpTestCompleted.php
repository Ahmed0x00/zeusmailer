<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SmtpTestCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    // 👇 Broadcast on a public or private channel
    public function broadcastOn()
    {
        // All SMTP test updates go to one channel
        return new Channel('smtp-tests');
    }

    public function broadcastAs()
    {
        // The event name that JS will listen for
        return 'SmtpTestCompleted';
    }
}
