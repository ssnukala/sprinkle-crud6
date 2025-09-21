import type { CRUD6Interface } from '@ssnukala/sprinkle-account/interfaces'
import type { SprunjerResponse } from '@userfrosting/sprinkle-core/interfaces'

/**
 * API Interfaces - What the API expects and what it returns
 *
 * This interface is tied to the `GroupsSprunjeAction` API, accessed at the
 * GET `/api/groups` endpoint.
 *
 * This api doesn't have a corresponding Request data interface.
 */
export interface CRUD6SprunjerResponse extends Omit<SprunjerResponse, 'rows'> {
    rows: CRUD6Interface[]
}
