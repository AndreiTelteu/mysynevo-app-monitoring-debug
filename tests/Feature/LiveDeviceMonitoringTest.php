<?php

namespace Tests\Feature;

use App\Events\LiveDevicesReset;
use App\Events\LiveDeviceUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LiveDeviceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_a_snapshot_and_broadcasts_the_update(): void
    {
        Event::fake([LiveDeviceUpdated::class]);

        $response = $this->postJson('/api/debug/live-devices/state', $this->payload());

        $response->assertOk()->assertJsonPath('accepted', true);
        $this->assertDatabaseHas('live_devices', [
            'device_id' => 'device-123',
            'session_id' => 'session-123',
            'sequence' => 1,
            'source_ip' => '127.0.0.1',
            'salesforce_contact_key' => 'contact-123',
            'salesforce_device_id' => 'sfmc-device-123',
        ]);
        Event::assertDispatched(LiveDeviceUpdated::class);
    }

    public function test_it_rejects_stale_sequences_from_the_same_session(): void
    {
        $this->postJson('/api/debug/live-devices/state', $this->payload(['sequence' => 3]))->assertOk();

        $this->postJson('/api/debug/live-devices/state', $this->payload(['sequence' => 2]))
            ->assertStatus(409)
            ->assertJsonPath('accepted', false);

        $this->assertDatabaseCount('live_devices', 1);
        $this->assertDatabaseHas('live_devices', ['device_id' => 'device-123', 'sequence' => 3]);
    }

    public function test_the_public_monitor_loads_existing_devices_from_the_database(): void
    {
        $this->postJson('/api/debug/live-devices/state', $this->payload())->assertOk();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('LiveDevices/Index')
                ->has('devices', 1)
                ->where('devices.0.deviceId', 'device-123')
                ->where('devices.0.salesforceMarketingCloud.contactKey', 'contact-123')
                ->where('devices.0.isOnline', true));
    }

    public function test_it_hides_a_device_for_all_monitor_clients(): void
    {
        $this->postJson('/api/debug/live-devices/state', $this->payload())->assertOk();
        Event::fake([LiveDeviceUpdated::class]);

        $this->patchJson(route('live-devices.hidden', 1), ['isHidden' => true])
            ->assertOk()
            ->assertJsonPath('device.isHidden', true);

        $this->assertDatabaseHas('live_devices', ['id' => 1, 'is_hidden' => true]);
        Event::assertDispatched(LiveDeviceUpdated::class);
    }

    public function test_it_permanently_resets_all_devices_and_broadcasts_the_reset(): void
    {
        $this->postJson('/api/debug/live-devices/state', $this->payload())->assertOk();
        Event::fake([LiveDevicesReset::class]);

        $this->deleteJson(route('live-devices.reset'))
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseCount('live_devices', 0);
        Event::assertDispatched(LiveDevicesReset::class);
    }

    private function payload(array $overrides = []): array
    {
        return [...[
            'schemaVersion' => 1,
            'deviceId' => 'device-123',
            'sessionId' => 'session-123',
            'sequence' => 1,
            'sentAt' => now()->toIso8601String(),
            'reason' => 'heartbeat',
            'environment' => ['key' => 'development', 'baseUrl' => 'https://my.synevo.ro/'],
            'app' => ['platform' => 'android', 'version' => '1.2.3', 'buildNumber' => '42', 'state' => 'active'],
            'device' => ['manufacturer' => 'Google', 'hardwareModel' => 'panther', 'modelName' => 'Pixel 7', 'deviceName' => 'Test phone', 'osVersion' => '15'],
            'network' => ['type' => 'wifi', 'isConnected' => true, 'isInternetReachable' => true, 'cellularGeneration' => null, 'carrier' => null, 'isConnectionExpensive' => false],
            'navigation' => ['url' => 'https://my.synevo.ro/results'],
            'salesforceMarketingCloud' => ['contactKey' => 'contact-123', 'deviceId' => 'sfmc-device-123'],
        ], ...$overrides];
    }
}
