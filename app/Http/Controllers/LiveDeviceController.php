<?php

namespace App\Http\Controllers;

use App\Events\LiveDevicesReset;
use App\Events\LiveDeviceUpdated;
use App\Models\LiveDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LiveDeviceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LiveDevices/Index', [
            'devices' => LiveDevice::query()->orderByDesc('is_pinned')->orderByDesc('last_seen_at')->get()->map->toMonitoringArray()->values(),
            'offlineAfterSeconds' => LiveDevice::OFFLINE_AFTER_SECONDS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_if(strlen($request->getContent()) > 32768, 413, 'Request payload is too large.');

        $data = $request->validate($this->rules());

        $device = DB::transaction(function () use ($data, $request) {
            $device = LiveDevice::query()->where('device_id', $data['deviceId'])->lockForUpdate()->first();

            if ($device && $device->session_id === $data['sessionId'] && $data['sequence'] <= $device->sequence) {
                return null;
            }

            $attributes = $this->attributes($data, $request->ip());

            if ($device) {
                $device->fill($attributes)->save();

                return $device->refresh();
            }

            return LiveDevice::query()->create(['device_id' => $data['deviceId'], ...$attributes]);
        });

        if (! $device) {
            return response()->json(['accepted' => false, 'reason' => 'stale_sequence'], 409);
        }

        LiveDeviceUpdated::dispatch($device);

        return response()->json(['accepted' => true, 'receivedAt' => $device->last_seen_at->toIso8601String()]);
    }

    public function updatePin(Request $request, LiveDevice $liveDevice): JsonResponse
    {
        $data = $request->validate(['isPinned' => ['required', 'boolean']]);
        $liveDevice->update(['is_pinned' => $data['isPinned']]);
        $liveDevice->refresh();

        LiveDeviceUpdated::dispatch($liveDevice);

        return response()->json(['device' => $liveDevice->toMonitoringArray()]);
    }

    public function updateHidden(Request $request, LiveDevice $liveDevice): JsonResponse
    {
        $data = $request->validate(['isHidden' => ['required', 'boolean']]);
        $liveDevice->update(['is_hidden' => $data['isHidden']]);
        $liveDevice->refresh();

        LiveDeviceUpdated::dispatch($liveDevice);

        return response()->json(['device' => $liveDevice->toMonitoringArray()]);
    }

    public function reset(): JsonResponse
    {
        LiveDevice::query()->delete();

        LiveDevicesReset::dispatch();

        return response()->json(['deleted' => true]);
    }

    private function attributes(array $data, ?string $sourceIp): array
    {
        return [
            'session_id' => $data['sessionId'], 'sequence' => $data['sequence'], 'reason' => $data['reason'],
            'environment_key' => $data['environment']['key'], 'environment_base_url' => $data['environment']['baseUrl'],
            'platform' => $data['app']['platform'], 'app_version' => $data['app']['version'], 'build_number' => $data['app']['buildNumber'], 'app_state' => $data['app']['state'],
            'manufacturer' => $data['device']['manufacturer'], 'hardware_model' => $data['device']['hardwareModel'], 'model_name' => $data['device']['modelName'], 'device_name' => $data['device']['deviceName'], 'os_version' => $data['device']['osVersion'],
            'network_type' => $data['network']['type'], 'network_connected' => $data['network']['isConnected'], 'network_internet_reachable' => $data['network']['isInternetReachable'], 'cellular_generation' => $data['network']['cellularGeneration'], 'carrier' => $data['network']['carrier'], 'connection_expensive' => $data['network']['isConnectionExpensive'],
            'navigation_url' => $data['navigation']['url'], 'salesforce_contact_key' => $data['salesforceMarketingCloud']['contactKey'], 'salesforce_device_id' => $data['salesforceMarketingCloud']['deviceId'], 'source_ip' => $sourceIp, 'last_seen_at' => now(),
        ];
    }

    private function rules(): array
    {
        $reasons = ['app_started', 'device_info_ready', 'network_changed', 'url_changed', 'environment_changed', 'app_foregrounded', 'app_backgrounded', 'heartbeat'];
        $networkTypes = ['wifi', 'cellular', 'ethernet', 'bluetooth', 'vpn', 'wimax', 'other', 'none', 'unknown'];

        return [
            'schemaVersion' => ['required', 'integer', 'in:1'], 'deviceId' => ['required', 'string', 'max:255'], 'sessionId' => ['required', 'string', 'max:255'], 'sequence' => ['required', 'integer', 'min:0'], 'sentAt' => ['required', 'date'], 'reason' => ['required', 'string', 'in:'.implode(',', $reasons)],
            'environment' => ['required', 'array:key,baseUrl'], 'environment.key' => ['required', 'string', 'max:100'], 'environment.baseUrl' => ['required', 'url', 'max:2048'],
            'app' => ['required', 'array:platform,version,buildNumber,state'], 'app.platform' => ['required', 'in:ios,android'], 'app.version' => ['required', 'string', 'max:100'], 'app.buildNumber' => ['required', 'string', 'max:100'], 'app.state' => ['required', 'in:active,background,inactive,unknown'],
            'device' => ['required', 'array:manufacturer,hardwareModel,modelName,deviceName,osVersion'], 'device.manufacturer' => ['nullable', 'string', 'max:255'], 'device.hardwareModel' => ['nullable', 'string', 'max:255'], 'device.modelName' => ['nullable', 'string', 'max:255'], 'device.deviceName' => ['nullable', 'string', 'max:255'], 'device.osVersion' => ['nullable', 'string', 'max:255'],
            'network' => ['required', 'array:type,isConnected,isInternetReachable,cellularGeneration,carrier,isConnectionExpensive'], 'network.type' => ['required', 'in:'.implode(',', $networkTypes)], 'network.isConnected' => ['nullable', 'boolean'], 'network.isInternetReachable' => ['nullable', 'boolean'], 'network.cellularGeneration' => ['nullable', 'in:2g,3g,4g,5g'], 'network.carrier' => ['nullable', 'string', 'max:255'], 'network.isConnectionExpensive' => ['nullable', 'boolean'],
            'navigation' => ['required', 'array:url'], 'navigation.url' => ['nullable', 'url', 'max:2048'],
            'salesforceMarketingCloud' => ['required', 'array:contactKey,deviceId'], 'salesforceMarketingCloud.contactKey' => ['nullable', 'string', 'max:255'], 'salesforceMarketingCloud.deviceId' => ['nullable', 'string', 'max:255'],
        ];
    }
}
