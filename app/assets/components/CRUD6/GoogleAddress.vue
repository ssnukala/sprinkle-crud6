<script setup lang="ts">
/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

import { ref, onMounted, watch, computed } from 'vue'
import { debugLog, debugError } from '../../utils/debug'

/**
 * Google Address Autocomplete Component
 * 
 * Uses Google Places Autocomplete API to capture full addresses
 * and automatically populate related address fields through geocoding.
 * 
 * Requires Google Maps JavaScript API key configured in environment.
 */

interface AddressComponents {
    street_number?: string
    route?: string
    locality?: string
    administrative_area_level_1?: string
    administrative_area_level_2?: string
    country?: string
    postal_code?: string
    postal_code_suffix?: string
    formatted_address?: string
    latitude?: number
    longitude?: number
}

const props = defineProps<{
    modelValue: string
    fieldKey: string
    placeholder?: string
    disabled?: boolean
    required?: boolean
    // Field mappings for geocoded components
    addressFields?: {
        addr_line1?: string
        addr_line2?: string
        city?: string
        state?: string
        zip?: string
        country?: string
        latitude?: string
        longitude?: string
    }
}>()

const emit = defineEmits(['update:modelValue', 'address-selected'])

const inputRef = ref<HTMLInputElement | null>(null)
const googleApiLoaded = ref(false)
const autocomplete = ref<any>(null)

/**
 * Get Google API key from config or environment
 */
const getGoogleApiKey = (): string | null => {
    // Check window config first (from backend config)
    if (typeof window !== 'undefined' && (window as any).CRUD6Config?.googleMapsApiKey) {
        return (window as any).CRUD6Config.googleMapsApiKey
    }
    
    // Check environment variable
    if (import.meta.env.VITE_GOOGLE_MAPS_API_KEY) {
        return import.meta.env.VITE_GOOGLE_MAPS_API_KEY
    }
    
    debugError('[GoogleAddress] Google Maps API key not found in config or environment')
    return null
}

/**
 * Load Google Maps JavaScript API
 */
const loadGoogleMapsAPI = (): Promise<void> => {
    return new Promise((resolve, reject) => {
        // Check if already loaded
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            googleApiLoaded.value = true
            resolve()
            return
        }

        const apiKey = getGoogleApiKey()
        if (!apiKey) {
            reject(new Error('Google Maps API key not configured'))
            return
        }

        // Create script element
        const script = document.createElement('script')
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`
        script.async = true
        script.defer = true

        script.onload = () => {
            googleApiLoaded.value = true
            debugLog('[GoogleAddress] Google Maps API loaded successfully')
            resolve()
        }

        script.onerror = () => {
            debugError('[GoogleAddress] Failed to load Google Maps API')
            reject(new Error('Failed to load Google Maps API'))
        }

        document.head.appendChild(script)
    })
}

/**
 * Parse address components from Google Place result
 */
const parseAddressComponents = (place: any): AddressComponents => {
    const components: AddressComponents = {
        formatted_address: place.formatted_address || '',
        latitude: place.geometry?.location?.lat() || undefined,
        longitude: place.geometry?.location?.lng() || undefined
    }

    if (!place.address_components) {
        return components
    }

    // Map Google's address component types to our schema
    const componentMap: Record<string, keyof AddressComponents> = {
        'street_number': 'street_number',
        'route': 'route',
        'locality': 'locality',
        'administrative_area_level_1': 'administrative_area_level_1',
        'administrative_area_level_2': 'administrative_area_level_2',
        'country': 'country',
        'postal_code': 'postal_code',
        'postal_code_suffix': 'postal_code_suffix'
    }

    place.address_components.forEach((component: any) => {
        const addressType = component.types[0]
        const mappedKey = componentMap[addressType]
        
        if (mappedKey) {
            // Use short_name for state/country codes, long_name for others
            const useShortName = ['administrative_area_level_1', 'country'].indexOf(addressType) !== -1
            components[mappedKey] = useShortName ? component.short_name : component.long_name
        }
    })

    return components
}

/**
 * Initialize Google Places Autocomplete
 */
const initAutocomplete = () => {
    if (!inputRef.value || !googleApiLoaded.value) {
        return
    }

    try {
        autocomplete.value = new google.maps.places.Autocomplete(inputRef.value, {
            types: ['address'],
            fields: ['address_components', 'formatted_address', 'geometry', 'name']
        })

        autocomplete.value.addListener('place_changed', () => {
            const place = autocomplete.value.getPlace()
            
            if (!place.geometry) {
                debugError('[GoogleAddress] No geometry data for selected place')
                return
            }

            debugLog('[GoogleAddress] Place selected', { place })

            // Parse address components
            const addressData = parseAddressComponents(place)

            // Update the input value
            emit('update:modelValue', addressData.formatted_address || '')

            // Build complete address data object based on field mappings
            const completeAddressData: Record<string, any> = {}

            if (props.addressFields) {
                // Map to schema field names
                if (props.addressFields.addr_line1 && addressData.street_number && addressData.route) {
                    completeAddressData[props.addressFields.addr_line1] = 
                        `${addressData.street_number} ${addressData.route}`
                }
                
                // addr_line2 can be used for apartment/suite number (usually empty from Google)
                if (props.addressFields.addr_line2) {
                    completeAddressData[props.addressFields.addr_line2] = ''
                }
                
                if (props.addressFields.city && addressData.locality) {
                    completeAddressData[props.addressFields.city] = addressData.locality
                }
                
                if (props.addressFields.state && addressData.administrative_area_level_1) {
                    completeAddressData[props.addressFields.state] = addressData.administrative_area_level_1
                }
                
                if (props.addressFields.zip) {
                    let zip = addressData.postal_code || ''
                    if (addressData.postal_code_suffix) {
                        zip += `-${addressData.postal_code_suffix}`
                    }
                    if (zip) {
                        completeAddressData[props.addressFields.zip] = zip
                    }
                }
                
                if (props.addressFields.country && addressData.country) {
                    completeAddressData[props.addressFields.country] = addressData.country
                }
                
                if (props.addressFields.latitude && addressData.latitude) {
                    completeAddressData[props.addressFields.latitude] = addressData.latitude
                }
                
                if (props.addressFields.longitude && addressData.longitude) {
                    completeAddressData[props.addressFields.longitude] = addressData.longitude
                }
            }

            // Emit the complete address data
            emit('address-selected', {
                rawData: addressData,
                mappedData: completeAddressData
            })

            debugLog('[GoogleAddress] Address data emitted', { completeAddressData })
        })
    } catch (error) {
        debugError('[GoogleAddress] Failed to initialize autocomplete', error)
    }
}

/**
 * Initialize component
 */
onMounted(async () => {
    try {
        await loadGoogleMapsAPI()
        initAutocomplete()
    } catch (error) {
        debugError('[GoogleAddress] Initialization failed', error)
    }
})

/**
 * Watch for modelValue changes from parent
 */
watch(() => props.modelValue, (newValue) => {
    if (inputRef.value && inputRef.value.value !== newValue) {
        inputRef.value.value = newValue || ''
    }
})

/**
 * Show warning if API key not configured
 */
const showApiKeyWarning = computed(() => {
    return !getGoogleApiKey()
})
</script>

<template>
    <div class="google-address-field">
        <input
            ref="inputRef"
            type="text"
            class="uk-input"
            :class="{ 'uk-form-danger': showApiKeyWarning }"
            :placeholder="placeholder || 'Enter address'"
            :disabled="disabled"
            :required="required"
            :value="modelValue"
            autocomplete="street-address"
            @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
        
        <div v-if="showApiKeyWarning" class="uk-text-danger uk-text-small uk-margin-small-top">
            ⚠️ Google Maps API key not configured. Set VITE_GOOGLE_MAPS_API_KEY or configure in backend.
        </div>
        
        <div class="uk-text-meta uk-text-small uk-margin-small-top">
            Start typing to search for an address
        </div>
    </div>
</template>

<style scoped>
.google-address-field {
    position: relative;
}

/* Style for Google autocomplete dropdown */
:deep(.pac-container) {
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    margin-top: 2px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

:deep(.pac-item) {
    padding: 8px 10px;
    font-size: 14px;
    cursor: pointer;
    border-top: 1px solid #e5e5e5;
}

:deep(.pac-item:first-child) {
    border-top: none;
}

:deep(.pac-item:hover) {
    background-color: #f8f8f8;
}

:deep(.pac-item-selected) {
    background-color: #1e87f0;
    color: white;
}

:deep(.pac-icon) {
    margin-right: 8px;
}

:deep(.pac-item-query) {
    font-weight: 600;
}
</style>
