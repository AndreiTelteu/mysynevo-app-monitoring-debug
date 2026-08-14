<script setup>
defineProps({
    title: { type: String, required: true },
    devices: { type: Array, required: true },
    isOnline: { type: Function, required: true },
    displayName: { type: Function, required: true },
    elapsed: { type: Function, required: true },
});

defineEmits(['toggle-pin']);
</script>

<template>
    <section class="device-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">{{ devices.length }} device{{ devices.length === 1 ? '' : 's' }}</p>
                <h2>{{ title }}</h2>
            </div>
            <span class="stream-label">LIVE SNAPSHOTS</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Pin</th><th>Presence</th><th>Device</th><th>App</th><th>Environment</th><th>Network</th><th>Current route</th><th>Last signal</th></tr></thead>
                <tbody>
                    <tr v-for="device in devices" :key="device.id">
                        <td><button class="pin-button" :class="{ active: device.isPinned }" type="button" :aria-label="device.isPinned ? 'Unpin device' : 'Pin device'" @click="$emit('toggle-pin', device)"><span>{{ device.isPinned ? 'PINNED' : 'PIN' }}</span></button></td>
                        <td><span class="presence" :class="isOnline(device) ? 'online' : 'offline'"><i></i>{{ isOnline(device) ? 'Online' : 'Offline' }}</span><small>{{ device.app.state }}</small></td>
                        <td><strong>{{ displayName(device) }}</strong><small>{{ device.device.manufacturer || 'Unknown maker' }} · {{ device.device.osVersion || 'Unknown OS' }}</small><code>{{ device.deviceId }}</code></td>
                        <td><strong>{{ device.app.platform }} {{ device.app.version }}</strong><small>build {{ device.app.buildNumber }} · {{ device.reason.replaceAll('_', ' ') }}</small></td>
                        <td><strong>{{ device.environment.key }}</strong><small class="url">{{ device.environment.baseUrl }}</small></td>
                        <td><strong>{{ device.network.type }}</strong><small>{{ device.network.carrier || 'No carrier' }} · {{ device.network.isInternetReachable === null ? 'reachability unknown' : device.network.isInternetReachable ? 'reachable' : 'unreachable' }}</small></td>
                        <td><span class="route">{{ device.navigation.url || 'No route reported' }}</span></td>
                        <td><strong>{{ elapsed(device) }}</strong><small>{{ new Date(device.lastSeenAt).toLocaleTimeString() }}</small></td>
                    </tr>
                    <tr v-if="!devices.length"><td colspan="8" class="empty-state">No devices match the active filters.</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<style scoped>
.device-section { max-width: 1400px; margin: 0 auto 4rem; }.section-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }.eyebrow { margin: 0 0 .55rem; color: var(--muted); font: 500 .68rem/1 'DM Mono', monospace; letter-spacing: .1em; text-transform: uppercase; }.section-heading h2 { margin: 0; font: 700 2rem/1 'Fraunces', serif; letter-spacing: -.04em; }.stream-label { color: var(--muted); font: .62rem 'DM Mono', monospace; letter-spacing: .1em; }.table-wrap { overflow-x: auto; border: 1px solid var(--ink); background: rgba(255,253,246,.82); box-shadow: 7px 7px 0 rgba(20,35,33,.11); } table { width: 100%; min-width: 1100px; border-collapse: collapse; } th { padding: .8rem 1rem; border-bottom: 1px solid var(--ink); background: #e9e7dc; text-align: left; font: 500 .62rem 'DM Mono', monospace; letter-spacing: .09em; text-transform: uppercase; } td { padding: 1rem; vertical-align: top; border-bottom: 1px solid var(--line); font-size: .8rem; } tbody tr:last-child td { border: 0; } tbody tr:hover { background: #edfad0; } td strong, td small, td code { display: block; } td strong { margin-bottom: .25rem; font-size: .82rem; } td small { color: var(--muted); font-size: .69rem; line-height: 1.4; } td code { max-width: 12rem; margin-top: .45rem; overflow: hidden; color: #9b4e36; font: .61rem 'DM Mono', monospace; text-overflow: ellipsis; white-space: nowrap; }.url,.route { max-width: 14rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }.route { color: #325d57; font: .69rem 'DM Mono', monospace; }.presence { display: flex; align-items: center; gap: .35rem; font-weight: 700; }.presence i { width: .5rem; height: .5rem; border-radius: 50%; }.presence.online { color: #167342; }.presence.online i { background: #38bb67; box-shadow: 0 0 0 3px rgba(56,187,103,.15); }.presence.offline { color: #a34f42; }.presence.offline i { background: #ca7868; }.presence + small { margin: .4rem 0 0 .85rem; }.pin-button { border: 1px solid var(--ink); background: transparent; padding: .35rem .45rem; color: var(--ink); cursor: pointer; font: .6rem 'DM Mono', monospace; }.pin-button.active { background: var(--lime); box-shadow: 2px 2px 0 var(--ink); }.empty-state { padding: 3rem; color: var(--muted); text-align: center; font: .8rem 'DM Mono', monospace; }
@media (max-width: 600px) { .stream-label { display: none; } }
</style>
