import type { CRUD6Interface } from './models/CRUD6Interface'
/**
 * API Interfaces - What the API expects and what it returns
 *
 * This interface is tied to the `CRUD6Api` API, accessed at the
 * GET `/api/crud6/{slug}/r` endpoint.
 *
 * This api doesn't have a corresponding Request data interface.
 */
export interface CRUD6Response extends CRUD6Interface {
  [key: string]: any
}
