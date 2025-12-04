/**
 * Translation utilities for CRUD6 components
 * 
 * Handles both translation keys and already-translated messages with {{placeholder}} syntax
 */

import type { TranslatorStore } from '@userfrosting/sprinkle-core/stores'

/**
 * Manually interpolate {{placeholder}} syntax in a message
 * 
 * @param message - The message with {{placeholders}}
 * @param params - Object with values to replace placeholders
 * @returns Message with placeholders replaced
 */
export function interpolatePlaceholders(message: string, params?: Record<string, any>): string {
    if (!params) return message
    
    let result = message
    
    // Replace {{key}} with params[key]
    Object.keys(params).forEach(key => {
        const value = params[key]
        // Handle both {{key}} and {{ key }} (with spaces)
        const regex = new RegExp(`\\{\\{\\s*${key}\\s*\\}\\}`, 'g')
        result = result.replace(regex, value !== null && value !== undefined ? String(value) : '')
    })
    
    return result
}

/**
 * Translate a key or interpolate an already-translated message
 * 
 * Handles two cases:
 * 1. Translation key (e.g., "CRUD6.DELETE_CONFIRM") - uses translator
 * 2. Already-translated message (e.g., "Are you sure...{{name}}?") - manually interpolates
 * 
 * @param translator - The UserFrosting translator store
 * @param key - Translation key or already-translated message
 * @param params - Parameters for placeholder interpolation
 * @param fallback - Fallback text if translation not found
 * @returns Translated and interpolated message
 */
export function translateOrInterpolate(
    translator: TranslatorStore,
    key: string,
    params?: Record<string, any>,
    fallback?: string
): string {
    // Check if it's a translation key (uppercase with dots/underscores) or already a message
    const isTranslationKey = /^[A-Z_]+(\.[A-Z_0-9]+)*$/.test(key)
    
    if (isTranslationKey) {
        // It's a translation key like "CRUD6.USER.PASSWORD_CHANGE_CONFIRM"
        // Use translator - it handles both translation AND interpolation
        const translated = translator.translate(key, params)
        
        // If translation failed (returns the key), try fallback
        if (translated === key && fallback) {
            return fallback
        }
        
        // If translation succeeded but has placeholders, interpolate them
        if (translated.includes('{{')) {
            return interpolatePlaceholders(translated, params)
        }
        
        return translated
    } else {
        // It's already a translated message with placeholders 
        // like "Are you sure you want to change the password for {{user_name}}?"
        // The backend already translated the key, so we just need to interpolate placeholders
        const interpolated = interpolatePlaceholders(key, params)
        return interpolated || fallback || key
    }
}

/**
 * Create a translation helper function bound to a translator instance
 * 
 * @param translator - The UserFrosting translator store
 * @returns A bound translation function
 */
export function createTranslationHelper(translator: TranslatorStore) {
    return (key: string, params?: Record<string, any>, fallback?: string): string => {
        return translateOrInterpolate(translator, key, params, fallback)
    }
}
