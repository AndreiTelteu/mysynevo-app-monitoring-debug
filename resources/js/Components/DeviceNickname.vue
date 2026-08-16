<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    nickname: { type: String, default: '' },
    placeholder: { type: String, default: '' },
});

const emit = defineEmits(['save']);

const value = ref(props.nickname);
const focused = ref(false);

watch(() => props.nickname, (next) => {
    if (!focused.value) value.value = next;
});

function onFocus() { focused.value = true; }

function onBlur() {
    focused.value = false;
    const next = value.value.trim();
    if (next === (props.nickname || '')) { value.value = props.nickname || ''; return; }
    value.value = next;
    emit('save', next);
}

function onKeydown(event) {
    if (event.key === 'Enter') event.target.blur();
}
</script>

<template>
    <input class="nickname-input" v-model="value" :placeholder="placeholder" type="text" aria-label="Device nickname" @focus="onFocus" @blur="onBlur" @keydown="onKeydown" />
</template>

<style scoped>
.nickname-input { display: block; max-width: 14rem; margin-bottom: .2rem; border: 0; border-radius: .18rem; background: transparent; padding: 0; color: #10202d; font: 700 .78rem 'DM Sans', sans-serif; outline: none; }.nickname-input::placeholder { color: #10202d; }.nickname-input:hover { background: #f1f7fd; }.nickname-input:focus { border: 1px solid #1473c7; background: #fff; padding: .22rem .38rem; box-shadow: 0 0 0 2px #e4f2ff; }
</style>
