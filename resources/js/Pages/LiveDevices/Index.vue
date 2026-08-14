<script setup>
import DeviceTable from '@/Components/LiveDeviceTable.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';

const props = defineProps({ devices: { type: Array, default: () => [] }, offlineAfterSeconds: { type: Number, default: 90 } });
const devices = ref([...props.devices]);
const now = ref(Date.now());
const filters = reactive({ query: '', platform: '', environment: '', status: '', network: '', appState: '' });
let timer;

const selectOptions = computed(() => ({ platforms: unique('app.platform'), environments: unique('environment.key'), networks: unique('network.type'), appStates: unique('app.state') }));
const filteredDevices = computed(() => devices.value.filter(matchesFilters).sort((a, b) => new Date(b.lastSeenAt) - new Date(a.lastSeenAt)));
const pinnedDevices = computed(() => filteredDevices.value.filter((device) => device.isPinned));
const allDevices = computed(() => filteredDevices.value.filter((device) => !device.isPinned));

function unique(path) { return [...new Set(devices.value.map((device) => valueAt(device, path)).filter(Boolean))].sort(); }
function valueAt(object, path) { return path.split('.').reduce((value, key) => value?.[key], object); }
function isOnline(device) { return now.value - new Date(device.lastSeenAt).getTime() <= props.offlineAfterSeconds * 1000; }
function matchesFilters(device) {
    const query = filters.query.trim().toLowerCase();
    const haystack = [device.deviceId, device.device.deviceName, device.device.manufacturer, device.device.modelName, device.app.version, device.navigation.url, device.network.carrier, device.salesforceMarketingCloud.contactKey, device.salesforceMarketingCloud.deviceId].filter(Boolean).join(' ').toLowerCase();
    return (!query || haystack.includes(query)) && (!filters.platform || device.app.platform === filters.platform) && (!filters.environment || device.environment.key === filters.environment) && (!filters.network || device.network.type === filters.network) && (!filters.appState || device.app.state === filters.appState) && (!filters.status || (filters.status === 'online' ? isOnline(device) : !isOnline(device)));
}
function updateDevice(updated) { const index = devices.value.findIndex((device) => device.id === updated.id); if (index === -1) devices.value.unshift(updated); else devices.value[index] = updated; }
async function togglePin(device) { const original = device.isPinned; device.isPinned = !original; try { updateDevice((await window.axios.patch(route('live-devices.pin', device.id), { isPinned: device.isPinned })).data.device); } catch { device.isPinned = original; } }
function displayName(device) { return device.device.deviceName || device.device.modelName || device.device.hardwareModel || 'Unnamed device'; }
function elapsed(device) { const seconds = Math.max(0, Math.floor((now.value - new Date(device.lastSeenAt).getTime()) / 1000)); if (seconds < 60) return `${seconds}s ago`; if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`; return `${Math.floor(seconds / 3600)}h ago`; }
function currentPath(url) { if (!url) return 'No route reported'; try { const parsed = new URL(url); return `${parsed.pathname}${parsed.search}${parsed.hash}` || '/'; } catch { return url.startsWith('/') ? url : 'No route reported'; } }
function resetFilters() { Object.assign(filters, { query: '', platform: '', environment: '', status: '', network: '', appState: '' }); }
onMounted(() => { timer = window.setInterval(() => { now.value = Date.now(); }, 1000); window.Echo.channel('live-devices').listen('.LiveDeviceUpdated', ({ device }) => updateDevice(device)); });
onBeforeUnmount(() => { window.clearInterval(timer); window.Echo.leave('live-devices'); });
</script>

<template>
    <Head title="Live Device Monitor" />
    <div class="monitor-shell">
        <section class="filter-panel" aria-label="Device filters">
            <div class="filter-title"><span class="mark"></span><strong>Live devices</strong><small>{{ devices.length }} tracked</small></div>
            <label class="search-field"><span>Search</span><input v-model="filters.query" type="search" placeholder="Device, route, SFMC key..." /></label>
            <label><span>Platform</span><select v-model="filters.platform"><option value="">All</option><option v-for="option in selectOptions.platforms" :key="option">{{ option }}</option></select></label>
            <label><span>Environment</span><select v-model="filters.environment"><option value="">All</option><option v-for="option in selectOptions.environments" :key="option">{{ option }}</option></select></label>
            <label><span>Network</span><select v-model="filters.network"><option value="">All</option><option v-for="option in selectOptions.networks" :key="option">{{ option }}</option></select></label>
            <label><span>State</span><select v-model="filters.appState"><option value="">All</option><option v-for="option in selectOptions.appStates" :key="option">{{ option }}</option></select></label>
            <label><span>Presence</span><select v-model="filters.status"><option value="">All</option><option value="online">Online</option><option value="offline">Offline</option></select></label>
            <button class="clear-button" type="button" @click="resetFilters">Reset</button>
        </section>
        <DeviceTable v-if="pinnedDevices.length" title="Pinned devices" :devices="pinnedDevices" :is-online="isOnline" :display-name="displayName" :elapsed="elapsed" :current-path="currentPath" @toggle-pin="togglePin" />
        <DeviceTable title="All devices" :devices="allDevices" :is-online="isOnline" :display-name="displayName" :elapsed="elapsed" :current-path="currentPath" @toggle-pin="togglePin" />
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap');
.monitor-shell { --ink: #004f99; --muted: #718ba4; --line: #dbe7f2; min-height: 100vh; padding: 1.15rem clamp(1rem, 2.3vw, 2.25rem) 2rem; background: #f3f7fb; color: var(--ink); font-family: 'DM Sans', sans-serif; }.filter-panel { max-width: 1536px; margin: 0 auto 1.25rem; display: grid; grid-template-columns: 1.3fr 2.4fr repeat(5, minmax(92px, .8fr)) auto; gap: .55rem; align-items: end; padding: .75rem; border-radius: .55rem; background: #fff; box-shadow: 0 1px 2px rgba(0,52,110,.08); }.filter-title { align-self: center; display: flex; align-items: center; gap: .45rem; white-space: nowrap; }.filter-title strong { font-size: .93rem; }.filter-title small { color: var(--muted); font: .62rem 'DM Mono', monospace; }.mark { width: .55rem; height: .55rem; border-radius: 50%; background: #fbb319; box-shadow: 0 0 0 4px #fff5da; }.filter-panel label { display: grid; gap: .25rem; }.filter-panel label > span { color: #7b91a5; font: 500 .57rem 'DM Mono', monospace; letter-spacing: .06em; text-transform: uppercase; }.filter-panel input,.filter-panel select { width: 100%; height: 2.25rem; padding: 0 .58rem; border: 1px solid var(--line); border-radius: .28rem; background: #fff; color: #174f86; font: .72rem 'DM Sans', sans-serif; outline: none; }.filter-panel input:focus,.filter-panel select:focus { border-color: #1473c7; box-shadow: 0 0 0 2px #e4f2ff; }.clear-button { height: 2.25rem; border: 1px solid #0066bd; border-radius: .28rem; background: #fff; padding: 0 .8rem; color: #0060b3; cursor: pointer; font: 500 .64rem 'DM Mono', monospace; }.clear-button:hover { background: #0060b3; color: #fff; }
@media (max-width: 1200px) { .filter-panel { grid-template-columns: 1fr 2fr repeat(3, minmax(100px,1fr)); }.clear-button { width: max-content; } } @media (max-width: 700px) { .monitor-shell { padding: .75rem; }.filter-panel { grid-template-columns: 1fr 1fr; }.filter-title,.search-field { grid-column: span 2; } }
</style>
