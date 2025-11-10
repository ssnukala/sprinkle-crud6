# DateTime Picker Integration

## Overview

CRUD6 supports datetime picker fields using popular Vue 3 compatible datetime libraries. This document explains how to integrate and use datetime pickers in your forms.

## Recommended Libraries

### 1. VCalendar (Recommended)

**Why VCalendar:**
- Native Vue 3 support
- Lightweight and customizable
- Supports date, datetime, and time pickers
- Great accessibility
- Works well with UIKit styling

**Installation:**
```bash
npm install v-calendar@next
```

**Usage in CRUD6:**
```json
{
  "appointment_date": {
    "type": "date",
    "label": "Appointment Date"
  },
  "event_datetime": {
    "type": "datetime",
    "label": "Event Date & Time"
  }
}
```

### 2. Vue3 Datepicker

**Installation:**
```bash
npm install @vuepic/vue-datepicker
```

### 3. Flatpickr (Vanilla JS)

**Installation:**
```bash
npm install flatpickr
```

## Implementation Guide

### Step 1: Install Library

Choose one of the recommended libraries and install it:

```bash
# VCalendar (Recommended)
npm install v-calendar@next

# OR Vue3 Datepicker
npm install @vuepic/vue-datepicker

# OR Flatpickr
npm install flatpickr
```

### Step 2: Create DateTimePicker Component

Create a reusable datetime picker component at:
`app/assets/components/CRUD6/DateTimePicker.vue`

**Example with VCalendar:**

```vue
<script setup lang="ts">
import { DatePicker } from 'v-calendar'
import 'v-calendar/style.css'

const props = defineProps<{
  modelValue: string | Date | null
  mode?: 'date' | 'datetime' | 'time'
  disabled?: boolean
  required?: boolean
  placeholder?: string
}>()

const emit = defineEmits(['update:modelValue'])

const updateValue = (value: any) => {
  emit('update:modelValue', value)
}
</script>

<template>
  <DatePicker
    :model-value="modelValue"
    :mode="mode || 'date'"
    :disabled="disabled"
    :is-required="required"
    @update:model-value="updateValue"
  >
    <template #default="{ inputValue, inputEvents }">
      <input
        class="uk-input"
        :value="inputValue"
        :placeholder="placeholder"
        :disabled="disabled"
        v-on="inputEvents"
      />
    </template>
  </DatePicker>
</template>

<style scoped>
/* Customize VCalendar to match UIKit theme */
:deep(.vc-container) {
  border: 1px solid #e5e5e5;
  border-radius: 4px;
}

:deep(.vc-header) {
  background-color: #f8f8f8;
  padding: 10px;
}

:deep(.vc-day.is-selected) {
  background-color: #1e87f0;
  color: white;
}
</style>
```

**Example with Flatpickr:**

```vue
<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'

const props = defineProps<{
  modelValue: string | null
  mode?: 'date' | 'datetime' | 'time'
  disabled?: boolean
  placeholder?: string
}>()

const emit = defineEmits(['update:modelValue'])

const inputRef = ref<HTMLInputElement | null>(null)
let picker: any = null

onMounted(() => {
  if (inputRef.value) {
    const enableTime = props.mode === 'datetime' || props.mode === 'time'
    const noCalendar = props.mode === 'time'
    
    picker = flatpickr(inputRef.value, {
      enableTime,
      noCalendar,
      dateFormat: props.mode === 'datetime' ? 'Y-m-d H:i' : 'Y-m-d',
      onChange: (selectedDates: Date[], dateStr: string) => {
        emit('update:modelValue', dateStr)
      }
    })
    
    if (props.modelValue) {
      picker.setDate(props.modelValue)
    }
  }
})

watch(() => props.modelValue, (newValue) => {
  if (picker && newValue) {
    picker.setDate(newValue)
  }
})
</script>

<template>
  <input
    ref="inputRef"
    type="text"
    class="uk-input"
    :placeholder="placeholder"
    :disabled="disabled"
  />
</template>

<style>
/* Customize Flatpickr to match UIKit theme */
.flatpickr-calendar {
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.flatpickr-day.selected {
  background-color: #1e87f0;
  border-color: #1e87f0;
}

.flatpickr-day:hover {
  background-color: #f8f8f8;
}
</style>
```

### Step 3: Update Form.vue

Update the Form component to use the DateTimePicker:

```vue
<script setup lang="ts">
import DateTimePicker from './DateTimePicker.vue'
// ... other imports
</script>

<template>
  <!-- ... -->
  
  <!-- Date input with picker -->
  <DateTimePicker
    v-else-if="field.type === 'date'"
    :id="fieldKey"
    mode="date"
    :placeholder="field.placeholder || field.label || fieldKey"
    :disabled="field.readonly"
    :required="field.required"
    v-model="formData[fieldKey]"
  />
  
  <!-- DateTime input with picker -->
  <DateTimePicker
    v-else-if="field.type === 'datetime'"
    :id="fieldKey"
    mode="datetime"
    :placeholder="field.placeholder || field.label || fieldKey"
    :disabled="field.readonly"
    :required="field.required"
    v-model="formData[fieldKey]"
  />
  
  <!-- ... -->
</template>
```

## Schema Configuration

### Date Field

```json
{
  "birth_date": {
    "type": "date",
    "label": "Date of Birth",
    "required": true,
    "listable": true,
    "validation": {
      "required": true
    }
  }
}
```

### DateTime Field

```json
{
  "event_start": {
    "type": "datetime",
    "label": "Event Start Time",
    "required": true,
    "listable": true,
    "validation": {
      "required": true
    }
  }
}
```

### Time Only Field (Optional)

```json
{
  "daily_reminder": {
    "type": "time",
    "label": "Daily Reminder Time",
    "listable": false
  }
}
```

## Advanced Configuration

### Date Range Validation

```json
{
  "appointment_date": {
    "type": "date",
    "validation": {
      "date": {
        "min": "2024-01-01",
        "max": "2025-12-31"
      }
    }
  }
}
```

### Default Values

```json
{
  "created_date": {
    "type": "datetime",
    "default": "now",
    "editable": false
  }
}
```

### Custom Format

```json
{
  "event_date": {
    "type": "date",
    "date_format": "Y-m-d",
    "display_format": "F j, Y"
  }
}
```

## Styling

### UIKit Integration

The datetime picker should match UIKit's design language:

```css
/* Match UIKit input styling */
.datetime-picker input {
  height: 40px;
  padding: 0 10px;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  font-size: 14px;
}

.datetime-picker input:focus {
  border-color: #1e87f0;
  outline: none;
}

/* Calendar popup styling */
.calendar-popup {
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}
```

### Dark Mode Support

```css
@media (prefers-color-scheme: dark) {
  .datetime-picker input {
    background-color: #2d2d2d;
    border-color: #444;
    color: #fff;
  }
  
  .calendar-popup {
    background-color: #2d2d2d;
    border-color: #444;
  }
}
```

## Complete Example

**Schema:**
```json
{
  "model": "events",
  "fields": {
    "title": {
      "type": "string",
      "label": "Event Title",
      "required": true
    },
    "event_date": {
      "type": "date",
      "label": "Event Date",
      "required": true,
      "placeholder": "Select date"
    },
    "event_time": {
      "type": "datetime",
      "label": "Event Date & Time",
      "required": true,
      "placeholder": "Select date and time"
    },
    "registration_deadline": {
      "type": "date",
      "label": "Registration Deadline",
      "validation": {
        "date": {
          "max": "event_date"
        }
      }
    }
  }
}
```

## Browser Compatibility

### Native HTML5 Date Inputs

For basic date/datetime inputs without a library:

```vue
<!-- Fallback to HTML5 native pickers -->
<input
  v-if="field.type === 'date'"
  type="date"
  class="uk-input"
  v-model="formData[fieldKey]"
/>

<input
  v-if="field.type === 'datetime'"
  type="datetime-local"
  class="uk-input"
  v-model="formData[fieldKey]"
/>
```

**Browser Support:**
- ✅ Chrome/Edge: Full support
- ✅ Safari: Full support
- ✅ Firefox: Full support
- ⚠️ Older browsers: May fall back to text input

## Best Practices

1. **Use datetime pickers for better UX** - Users make fewer input errors
2. **Match your app's theme** - Customize picker styling to match UIKit
3. **Validate date ranges** - Prevent users from selecting invalid dates
4. **Provide placeholders** - Show expected format (e.g., "MM/DD/YYYY")
5. **Test on mobile** - Ensure touch-friendly date selection
6. **Consider timezones** - Store in UTC, display in user's timezone
7. **Accessibility** - Ensure keyboard navigation works

## Troubleshooting

### Picker not showing

**Solution:** Ensure CSS is imported:
```typescript
import 'v-calendar/style.css'
// or
import 'flatpickr/dist/flatpickr.min.css'
```

### Date format issues

**Solution:** Normalize date format in schema:
```json
{
  "date_format": "Y-m-d",
  "display_format": "F j, Y"
}
```

### Timezone problems

**Solution:** Always store in UTC:
```typescript
const utcDate = new Date(localDate).toISOString()
```

## See Also

- [VCalendar Documentation](https://vcalendar.io/)
- [Vue3 Datepicker Documentation](https://vue3datepicker.com/)
- [Flatpickr Documentation](https://flatpickr.js.org/)
- [FIELD_TYPES_REFERENCE.md](FIELD_TYPES_REFERENCE.md)
