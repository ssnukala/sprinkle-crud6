/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

import CRUD6RoutesImport from './CRUD6Routes'
import TestRoutesImport from './TestRoutes'

const CRUD6Routes = [
    ...CRUD6RoutesImport,
    ...TestRoutesImport,
]

export default CRUD6Routes

export {
    CRUD6RoutesImport,
    TestRoutesImport
}
