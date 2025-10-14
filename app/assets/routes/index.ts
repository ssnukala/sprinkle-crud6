import CRUD6RoutesImport from './CRUD6Routes'

const CRUD6Routes = [
    { path: '', redirect: { name: 'crud6.list' } },
    ...CRUD6RoutesImport,
]

export default CRUD6Routes
