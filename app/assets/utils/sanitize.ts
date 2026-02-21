/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * Lightweight HTML sanitizer for v-html content.
 *
 * Strips all HTML tags except a whitelist of safe formatting tags.
 * Removes event handler attributes (onclick, onerror, etc.) and
 * javascript: URIs from any remaining tags.
 *
 * Use this instead of raw v-html when rendering translated strings
 * that may include user-provided data (e.g., record field values
 * interpolated into translation templates).
 */

/** Tags allowed through the sanitizer */
const ALLOWED_TAGS = ['strong', 'em', 'b', 'i', 'u', 'br', 'p', 'span', 'ul', 'ol', 'li', 'a', 'small', 'sub', 'sup']

/** Attributes allowed on remaining tags */
const ALLOWED_ATTRS = ['class', 'href', 'target', 'rel', 'title']

/**
 * Sanitize an HTML string by stripping dangerous tags and attributes.
 *
 * @param html Raw HTML string (e.g., from translator output)
 * @returns Sanitized HTML safe for v-html rendering
 */
export function sanitizeHtml(html: string): string {
    if (!html) return ''

    // Use DOMParser for robust parsing (available in all modern browsers)
    const parser = new DOMParser()
    const doc = parser.parseFromString(html, 'text/html')

    // Recursively clean nodes
    cleanNode(doc.body)

    return doc.body.innerHTML
}

/**
 * Recursively clean a DOM node, removing disallowed tags and attributes.
 */
function cleanNode(node: Node): void {
    const children = Array.from(node.childNodes)

    for (const child of children) {
        if (child.nodeType === Node.ELEMENT_NODE) {
            const el = child as Element
            const tagName = el.tagName.toLowerCase()

            if (!ALLOWED_TAGS.includes(tagName)) {
                // Replace disallowed element with its text content
                const text = document.createTextNode(el.textContent || '')
                node.replaceChild(text, child)
            } else {
                // Remove disallowed attributes
                const attrs = Array.from(el.attributes)
                for (const attr of attrs) {
                    const name = attr.name.toLowerCase()
                    if (!ALLOWED_ATTRS.includes(name) || name.startsWith('on')) {
                        el.removeAttribute(attr.name)
                    }
                    // Strip javascript: URIs
                    if (name === 'href' && attr.value.trim().toLowerCase().startsWith('javascript:')) {
                        el.removeAttribute(attr.name)
                    }
                }
                // Recurse into children
                cleanNode(el)
            }
        }
    }
}
