<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import DeviceTable from '@/Components/LiveDeviceTable.vue';

const props = defineProps({
    devices: { type: Array, default: () => [] },
    offlineAfterSeconds: { type: Number, default: 90 },
});

const devices = ref([...props.devices]);
const now = ref(Date.now());
const filters = reactive({ query: '', platform: '', environment: '', status: '', network: '', appState: '' });
let timer;

const selectOptions = computed(() => ({
    platforms: unique('app.platform'), environments: unique('environment.key'), networks: unique('network.type'), appStates: unique('app.state'),
}));

const filteredDevices = computed(() => devices.value
    .filter(matchesFilters)
    .sort((a, b) => new Date(b.lastSeenAt) - new Date(a.lastSeenAt)));

const pinnedDevices = computed(() => filteredDevices.value.filter((device) => device.isPinned));
const allDevices = computed(() => filteredDevices.value.filter((device) => !device.isPinned));
const onlineCount = computed(() => devices.value.filter(isOnline).length);

function unique(path) {
    return [...new Set(devices.value.map((device) => valueAt(device, path)).filter(Boolean))].sort();
}

function valueAt(object, path) {
    return path.split('.').reduce((value, key) => value?.[key], object);
}

function isOnline(device) {
    return now.value - new Date(device.lastSeenAt).getTime() <= props.offlineAfterSeconds * 1000;
}

function matchesFilters(device) {
    const query = filters.query.trim().toLowerCase();
    const haystack = [device.deviceId, device.device.deviceName, device.device.manufacturer, device.device.modelName, device.device.hardwareModel, device.app.version, device.app.buildNumber, device.navigation.url, device.network.carrier].filter(Boolean).join(' ').toLowerCase();
    return (!query || haystack.includes(query))
        && (!filters.platform || device.app.platform === filters.platform)
        && (!filters.environment || device.environment.key === filters.environment)
        && (!filters.network || device.network.type === filters.network)
        && (!filters.appState || device.app.state === filters.appState)
        && (!filters.status || (filters.status === 'online' ? isOnline(device) : !isOnline(device)));
}

function updateDevice(updated) {
    const index = devices.value.findIndex((device) => device.id === updated.id);
    if (index === -1) devices.value.unshift(updated);
    else devices.value[index] = updated;
}

async function togglePin(device) {
    const original = device.isPinned;
    device.isPinned = !original;
    try {
        const response = await window.axios.patch(route('live-devices.pin', device.id), { isPinned: device.isPinned });
        updateDevice(response.data.device);
    } catch {
        device.isPinned = original;
    }
}

function displayName(device) {
    return device.device.deviceName || device.device.modelName || device.device.hardwareModel || 'Unnamed device';
}

function elapsed(device) {
    const seconds = Math.max(0, Math.floor((now.value - new Date(device.lastSeenAt).getTime()) / 1000));
    if (seconds < 60) return `${seconds}s ago`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    return `${Math.floor(seconds / 3600)}h ago`;
}

function resetFilters() { Object.assign(filters, { query: '', platform: '', environment: '', status: '', network: '', appState: '' }); }

onMounted(() => {
    timer = window.setInterval(() => { now.value = Date.now(); }, 1000);
    window.Echo.channel('live-devices').listen('.LiveDeviceUpdated', ({ device }) => updateDevice(device));
});

onBeforeUnmount(() => { window.clearInterval(timer); window.Echo.leave('live-devices'); });
</script>

<template>
    <Head title="Live Device Monitor" />

    <div class="monitor-shell">
            <section class="monitor-hero">
                <div>
                    <p class="eyebrow"><span class="pulse"></span> Reverb stream connected</p>
                    <h1>Live device<br /><em>watchtower.</em></h1>
                    <p class="hero-copy">A non-paginated live view of every mobile client reporting into this environment.</p>
                </div>
                <div class="hero-stats" aria-label="Device statistics">
                    <div><strong>{{ onlineCount }}</strong><span>online now</span></div>
                    <div><strong>{{ devices.length - onlineCount }}</strong><span>offline</span></div>
                    <div><strong>{{ pinnedDevices.length }}</strong><span>pinned</span></div>
                </div>
            </section>

            <section class="filter-deck" aria-label="Device filters">
                <label class="search-field"><span>Search</span><input v-model="filters.query" type="search" placeholder="Device, identifier, route, carrier..." /></label>
                <label><span>Platform</span><select v-model="filters.platform"><option value="">All platforms</option><option v-for="option in selectOptions.platforms" :key="option">{{ option }}</option></select></label>
                <label><span>Environment</span><select v-model="filters.environment"><option value="">All environments</option><option v-for="option in selectOptions.environments" :key="option">{{ option }}</option></select></label>
                <label><span>Network</span><select v-model="filters.network"><option value="">All networks</option><option v-for="option in selectOptions.networks" :key="option">{{ option }}</option></select></label>
                <label><span>App state</span><select v-model="filters.appState"><option value="">Any state</option><option v-for="option in selectOptions.appStates" :key="option">{{ option }}</option></select></label>
                <label><span>Presence</span><select v-model="filters.status"><option value="">Online + offline</option><option value="online">Online</option><option value="offline">Offline</option></select></label>
                <button class="clear-button" type="button" @click="resetFilters">Clear filters</button>
            </section>

            <DeviceTable v-if="pinnedDevices.length" title="Pinned devices" :devices="pinnedDevices" :is-online="isOnline" :display-name="displayName" :elapsed="elapsed" @toggle-pin="togglePin" />
            <DeviceTable title="All devices" :devices="allDevices" :is-online="isOnline" :display-name="displayName" :elapsed="elapsed" @toggle-pin="togglePin" />
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap');
.monitor-shell { --ink: #142321; --paper: #f5f2e9; --lime: #c6ff3c; --line: #d7d4c9; --muted: #68736e; min-height: calc(100vh - 4rem); padding: clamp(1.5rem, 4vw, 4rem); background: radial-gradient(circle at 90% 0%, #dff2b2 0, transparent 26rem), repeating-linear-gradient(90deg, transparent 0 89px, rgba(20,35,33,.035) 90px), var(--paper); color: var(--ink); font-family: 'DM Sans', sans-serif; }
.monitor-hero { max-width: 1400px; margin: auto; display: flex; align-items: end; justify-content: space-between; gap: 2rem; padding-bottom: 3rem; border-bottom: 1px solid var(--ink); }.eyebrow { margin: 0 0 .55rem; color: var(--muted); font: 500 .68rem/1 'DM Mono', monospace; letter-spacing: .1em; text-transform: uppercase; }.pulse { display: inline-block; width: .55rem; height: .55rem; margin-right: .4rem; border-radius: 50%; background: #51bf62; box-shadow: 0 0 0 4px rgba(81,191,98,.15); }.monitor-hero h1 { margin: 0; font: 700 clamp(3.2rem, 7vw, 6.9rem)/.84 'Fraunces', serif; letter-spacing: -.075em; }.monitor-hero h1 em { color: #f05f3f; font-style: italic; }.hero-copy { max-width: 31rem; margin: 1.4rem 0 0; color: var(--muted); font-size: 1rem; line-height: 1.6; }.hero-stats { display: flex; border: 1px solid var(--ink); background: #fffdf6; }.hero-stats div { min-width: 6.5rem; padding: 1rem 1.2rem; border-left: 1px solid var(--ink); }.hero-stats div:first-child { border: 0; }.hero-stats strong,.hero-stats span { display: block; }.hero-stats strong { font: 700 2rem/1 'Fraunces', serif; }.hero-stats span { margin-top: .35rem; color: var(--muted); font: .63rem 'DM Mono'; text-transform: uppercase; }
.filter-deck { max-width: 1400px; margin: 2rem auto 3.5rem; display: grid; grid-template-columns: 2fr repeat(5, minmax(120px,1fr)) auto; gap: .75rem; align-items: end; }.filter-deck label { display: grid; gap: .35rem; }.filter-deck span { color: var(--muted); font: 500 .65rem 'DM Mono'; letter-spacing: .08em; text-transform: uppercase; }.filter-deck input,.filter-deck select { width: 100%; height: 2.85rem; padding: 0 .8rem; border: 1px solid var(--line); border-radius: 0; background: rgba(255,253,246,.8); color: var(--ink); font: .82rem 'DM Sans'; outline: none; }.filter-deck input:focus,.filter-deck select:focus { border-color: var(--ink); box-shadow: 3px 3px 0 var(--lime); }.clear-button { height: 2.85rem; border: 1px solid var(--ink); background: transparent; padding: 0 1rem; color: var(--ink); cursor: pointer; font: 500 .68rem 'DM Mono'; text-transform: uppercase; }.clear-button:hover { background: var(--ink); color: var(--paper); }
@media (max-width: 1000px) { .filter-deck { grid-template-columns: repeat(3,1fr); }.search-field { grid-column: span 3; }.clear-button { width: max-content; }.monitor-hero { align-items: start; flex-direction: column; }.hero-stats { width: 100%; }.hero-stats div { flex: 1; } } @media (max-width: 600px) { .monitor-shell { padding: 1.2rem; }.filter-deck { grid-template-columns: 1fr 1fr; }.search-field { grid-column: span 2; }.monitor-hero h1 { font-size: 3.6rem; }.hero-stats strong { font-size: 1.55rem; }.hero-stats div { min-width: 0; padding: .8rem; } }
</style>
