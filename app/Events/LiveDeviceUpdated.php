<?php

namespace App\Events;

use App\Models\LiveDevice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveDeviceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public LiveDevice $device) {}

    public function broadcastOn(): array
    {
        return [new Channel('live-devices')];
    }

    public function broadcastAs(): string
    {
        return 'LiveDeviceUpdated';
    }

    public function broadcastWith(): array
    {
        return ['device' => $this->device->toMonitoringArray()];
    }
}
