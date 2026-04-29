<?php

namespace App\Events;

use App\Models\LiveClass;
use App\Models\LiveClassMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveClassMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public LiveClass $liveClass,
        public LiveClassMessage $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('live-class.'.$this->liveClass->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'live-class.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'user_name' => $this->message->user?->name,
                'user_role' => $this->message->user?->role,
                'message' => $this->message->message,
                'created_at' => optional($this->message->created_at)->toIso8601String(),
            ],
        ];
    }
}
