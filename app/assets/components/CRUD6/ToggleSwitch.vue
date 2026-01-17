<script setup lang="ts">
/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

import { computed } from 'vue'

/**
 * Toggle Switch Component
 * 
 * A custom toggle switch component for boolean fields that provides
 * a modern UI alternative to standard checkboxes.
 */

const props = defineProps<{
    modelValue: boolean
    disabled?: boolean
    id?: string
    dataTest?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
}>()

const isChecked = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})
</script>

<template>
    <label class="toggle-switch" :class="{ 'toggle-switch-disabled': disabled }">
        <input
            type="checkbox"
            :id="id"
            :data-test="dataTest"
            :disabled="disabled"
            v-model="isChecked"
            class="toggle-switch-checkbox" />
        <span class="toggle-switch-slider"></span>
        <span class="toggle-switch-label">
            {{ isChecked ? 'Enabled' : 'Disabled' }}
        </span>
    </label>
</template>

<style scoped>
.toggle-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
    gap: 10px;
}

.toggle-switch-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.toggle-switch-checkbox {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-switch-slider {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
    background-color: #ccc;
    border-radius: 26px;
    transition: background-color 0.3s ease;
}

.toggle-switch-slider::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    left: 3px;
    top: 3px;
    background-color: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
}

.toggle-switch-checkbox:checked + .toggle-switch-slider {
    background-color: #1e87f0; /* UIKit primary color */
}

.toggle-switch-checkbox:checked + .toggle-switch-slider::before {
    transform: translateX(24px);
}

.toggle-switch-checkbox:focus + .toggle-switch-slider {
    box-shadow: 0 0 0 3px rgba(30, 135, 240, 0.2);
}

.toggle-switch-disabled .toggle-switch-slider {
    cursor: not-allowed;
}

.toggle-switch-label {
    font-size: 14px;
    font-weight: 500;
    color: #666;
    min-width: 70px;
}

.toggle-switch-checkbox:checked ~ .toggle-switch-label {
    color: #1e87f0;
}
</style>
