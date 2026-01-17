/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

import type { ApiResponse, SprunjerResponse } from '@userfrosting/sprinkle-core/interfaces'
import type { CRUD6Interface } from './models/CRUD6Interface'

/**
 * CRUD6 API Type Definitions
 * 
 * This file consolidates all CRUD6 API request and response types.
 */

// ============================================================================
// Single Record Operations
// ============================================================================

/**
 * Response from GET /api/crud6/{model}/{id}
 */
export interface CRUD6Response extends CRUD6Interface {
    [key: string]: any
}

/**
 * Request for POST /api/crud6/{model}
 */
export interface CRUD6CreateRequest {
    [key: string]: any
}

/**
 * Response from POST /api/crud6/{model}
 */
export type CRUD6CreateResponse = ApiResponse

/**
 * Request for PUT /api/crud6/{model}/{id}
 */
export interface CRUD6EditRequest {
    [key: string]: any
}

/**
 * Response from PUT /api/crud6/{model}/{id}
 */
export type CRUD6EditResponse = ApiResponse

/**
 * Response from DELETE /api/crud6/{model}/{id}
 */
export type CRUD6DeleteResponse = ApiResponse

// ============================================================================
// List Operations (Sprunje)
// ============================================================================

/**
 * Response from GET /api/crud6/{model} (Sprunje endpoint)
 */
export interface CRUD6SprunjerResponse extends Omit<SprunjerResponse, 'rows'> {
    rows: CRUD6Interface[]
}
