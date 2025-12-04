/**
 * CRUD6 Locale Exports
 * 
 * Provides CRUD6 translations for frontend Vue i18n.
 * 
 * These locale files are generated from app/locale/{locale}/messages.php
 * to be consumed by the Vue i18n system.
 * 
 * Usage in your UserFrosting app:
 * ```typescript
 * import { en_US, fr_FR } from '@ssnukala/sprinkle-crud6/locale'
 * 
 * // Add to your i18n messages
 * const i18n = createI18n({
 *   messages: {
 *     en_US: { ...existingMessages, ...en_US },
 *     fr_FR: { ...existingMessages, ...fr_FR }
 *   }
 * })
 * ```
 */

import en_US from './en_US.json'
import fr_FR from './fr_FR.json'

export { en_US, fr_FR }

export default {
    en_US,
    fr_FR
}
