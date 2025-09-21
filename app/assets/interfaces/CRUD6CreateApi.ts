import type { ApiResponse } from '@userfrosting/sprinkle-core/interfaces'

/**
 * Interfaces - What the API expects and what it returns
 */
export interface CRUD6CreateRequest {
    slug: string
    name: string
    description: string
    icon: string
}

export type CRUD6CreateResponse = ApiResponse
