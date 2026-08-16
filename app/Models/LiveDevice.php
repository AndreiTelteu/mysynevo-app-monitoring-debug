<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveDevice extends Model
{
    public const OFFLINE_AFTER_SECONDS = 90;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'network_connected' => 'boolean',
            'network_internet_reachable' => 'boolean',
            'connection_expensive' => 'boolean',
            'is_pinned' => 'boolean',
            'is_hidden' => 'boolean',
            'last_seen_at' => 'immutable_datetime',
        ];
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at->greaterThanOrEqualTo(now()->subSeconds(self::OFFLINE_AFTER_SECONDS));
    }

    public function toMonitoringArray(): array
    {
        return [
            'id' => $this->id,
            'deviceId' => $this->device_id,
            'nickname' => $this->nickname,
            'sessionId' => $this->session_id,
            'sequence' => $this->sequence,
            'reason' => $this->reason,
            'environment' => ['key' => $this->environment_key, 'baseUrl' => $this->environment_base_url],
            'app' => ['platform' => $this->platform, 'version' => $this->app_version, 'buildNumber' => $this->build_number, 'state' => $this->app_state],
            'device' => ['manufacturer' => $this->manufacturer, 'hardwareModel' => $this->hardware_model, 'modelName' => $this->model_name, 'deviceName' => $this->device_name, 'osVersion' => $this->os_version],
            'network' => ['type' => $this->network_type, 'isConnected' => $this->network_connected, 'isInternetReachable' => $this->network_internet_reachable, 'cellularGeneration' => $this->cellular_generation, 'carrier' => $this->carrier, 'isConnectionExpensive' => $this->connection_expensive],
            'navigation' => ['url' => $this->navigation_url],
            'salesforceMarketingCloud' => ['contactKey' => $this->salesforce_contact_key, 'deviceId' => $this->salesforce_device_id],
            'lastSeenAt' => $this->last_seen_at->toIso8601String(),
            'isPinned' => $this->is_pinned,
            'isHidden' => $this->is_hidden,
            'isOnline' => $this->isOnline(),
        ];
    }
}
