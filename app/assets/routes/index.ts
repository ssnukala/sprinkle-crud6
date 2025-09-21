import CRUD6RoutesImport from './CRUD6Routes'

const CRUD6Routes = [
    { path: '', redirect: { name: 'admin.dashboard' } },
    ...CRUD6RoutesImport,
]

export default CRUD6Routes

export {
    CRUD6RoutesImport as CRUD6Routes
}
