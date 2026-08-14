<script setup>
import { ref } from 'vue';

defineProps({
    title: { type: String, required: true },
    devices: { type: Array, required: true },
    isOnline: { type: Function, required: true },
    displayName: { type: Function, required: true },
    elapsed: { type: Function, required: true },
    currentPath: { type: Function, required: true },
});

defineEmits(['toggle-pin']);

const copiedValue = ref('');

async function copy(value) {
    if (!value) return;

    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(value);
    } else {
        const input = document.createElement('textarea');
        input.value = value;
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
    }

    copiedValue.value = value;
    window.setTimeout(() => { copiedValue.value = ''; }, 1500);
}
</script>

<template>
    <section class="device-section">
        <div class="section-heading">
            <h2>{{ title }} <span>{{ devices.length }}</span></h2>
            <span class="stream-label">LIVE DEVICE STATE</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Pin</th><th>Device</th><th>Presence</th><th>App</th><th>Network</th><th>Environment / route</th><th>Salesforce Marketing Cloud</th><th>Last seen</th></tr></thead>
                <tbody>
                    <tr v-for="device in devices" :key="device.id">
                        <td><button class="pin-button" :class="{ active: device.isPinned }" type="button" :aria-label="device.isPinned ? 'Unpin device' : 'Pin device'" @click="$emit('toggle-pin', device)">{{ device.isPinned ? 'PINNED' : 'PIN' }}</button></td>
                        <td><strong>{{ displayName(device) }}</strong><small>{{ device.device.manufacturer || 'Unknown maker' }} · {{ device.device.osVersion || 'Unknown OS' }}</small><code>{{ device.deviceId }}</code></td>
                        <td><span class="presence" :class="isOnline(device) ? 'online' : 'offline'"><i></i>{{ isOnline(device) ? 'Online' : 'Offline' }}</span><small>{{ device.app.state }}</small></td>
                        <td><strong>{{ device.app.platform }} {{ device.app.version }}</strong><small>build {{ device.app.buildNumber }} · {{ device.reason.replaceAll('_', ' ') }}</small></td>
                        <td><strong>{{ device.network.type }}</strong><small>{{ device.network.carrier || 'No carrier' }} · {{ device.network.isInternetReachable === null ? 'reachability unknown' : device.network.isInternetReachable ? 'reachable' : 'unreachable' }}</small></td>
                        <td class="environment-route"><strong>{{ device.environment.key }}</strong><small>{{ device.environment.baseUrl }}</small><code>{{ currentPath(device.navigation.url) }}</code></td>
                        <td class="salesforce-cell">
                            <button v-if="device.salesforceMarketingCloud.contactKey" type="button" class="copy-value" :title="`Copy ${device.salesforceMarketingCloud.contactKey}`" @click="copy(device.salesforceMarketingCloud.contactKey)">{{ copiedValue === device.salesforceMarketingCloud.contactKey ? 'Copied' : device.salesforceMarketingCloud.contactKey }}</button>
                            <strong v-else>No contact key</strong>
                            <button v-if="device.salesforceMarketingCloud.deviceId" type="button" class="copy-value copy-code" :title="`Copy ${device.salesforceMarketingCloud.deviceId}`" @click="copy(device.salesforceMarketingCloud.deviceId)">{{ copiedValue === device.salesforceMarketingCloud.deviceId ? 'Copied' : device.salesforceMarketingCloud.deviceId }}</button>
                            <code v-else>No SFMC device ID</code>
                        </td>
                        <td><strong>{{ elapsed(device) }}</strong><small>{{ new Date(device.lastSeenAt).toLocaleTimeString() }}</small></td>
                    </tr>
                    <tr v-if="!devices.length"><td colspan="8" class="empty-state">No devices match the active filters.</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<style scoped>
.device-section { max-width: 1536px; margin: 0 auto 2rem; }.section-heading { display: flex; align-items: center; justify-content: space-between; margin: 0 0 .6rem; }.section-heading h2 { margin: 0; color: #0052a5; font: 700 1.05rem/1 'DM Sans', sans-serif; }.section-heading h2 span { display: inline-grid; min-width: 1.45rem; height: 1.45rem; margin-left: .4rem; place-items: center; border-radius: 50%; background: #e5f1ff; color: #0069c7; font: 500 .67rem 'DM Mono', monospace; vertical-align: middle; }.stream-label { color: #1e2933; font: .61rem 'DM Mono', monospace; letter-spacing: .09em; }.table-wrap { overflow-x: auto; border-radius: .55rem; background: #fff; box-shadow: 0 1px 2px rgba(0,52,110,.08); } table { width: 100%; min-width: 1260px; border-collapse: collapse; } th { padding: .7rem 1rem; border-bottom: 1px solid #e5edf5; background: #f8fbfe; color: #263746; text-align: left; font: 500 .62rem 'DM Mono', monospace; letter-spacing: .06em; text-transform: uppercase; } td { padding: .78rem 1rem; vertical-align: top; border-bottom: 1px solid #edf2f7; color: #073f7a; font-size: .78rem; } tbody tr:last-child td { border: 0; } tbody tr:hover { background: #f6fbff; } td strong, td small, td code { display: block; } td strong { margin-bottom: .2rem; color: #10202d; font-size: .78rem; } td small { max-width: 14rem; overflow: hidden; color: #1f2d38; font-size: .67rem; line-height: 1.4; text-overflow: ellipsis; white-space: nowrap; } td code { max-width: 15rem; margin-top: .32rem; overflow: hidden; color: #0d477d; font: .61rem 'DM Mono', monospace; text-overflow: ellipsis; white-space: nowrap; }.environment-route small { color: #164f87; }.environment-route code { color: #005eb8; }.salesforce-cell { min-width: 13rem; }.copy-value { display: block; max-width: 15rem; overflow: hidden; border: 0; border-radius: .18rem; background: transparent; padding: 0; color: #10202d; cursor: copy; font: 700 .78rem 'DM Sans', sans-serif; text-align: left; text-overflow: ellipsis; white-space: nowrap; }.copy-value:hover,.copy-value:focus-visible { background: #e4f2ff; color: #005eb8; outline: none; }.copy-code { margin-top: .32rem; color: #0d477d; font: .61rem 'DM Mono', monospace; font-weight: 400; }.presence { display: flex; align-items: center; gap: .35rem; font-weight: 700; }.presence i { width: .45rem; height: .45rem; border-radius: 50%; }.presence.online { color: #16835a; }.presence.online i { background: #2ab477; box-shadow: 0 0 0 3px rgba(42,180,119,.13); }.presence.offline { color: #b04d46; }.presence.offline i { background: #db8179; }.presence + small { margin: .35rem 0 0 .8rem; }.pin-button { border: 1px solid #bcd3ea; border-radius: .25rem; background: #fff; padding: .28rem .42rem; color: #0063b8; cursor: pointer; font: 500 .58rem 'DM Mono', monospace; }.pin-button:hover,.pin-button.active { border-color: #ffbd26; background: #fff8e7; color: #d78c00; }.empty-state { padding: 2.5rem; color: #1f2d38; text-align: center; font: .75rem 'DM Mono', monospace; }
@media (max-width: 600px) { .stream-label { display: none; } }
</style>
