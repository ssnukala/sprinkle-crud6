import CRUD6Routes from './CRUD6Routes'

const CRUD6Routes = [
    { path: '', redirect: { name: 'admin.dashboard' } },
    ...CRUD6Routes,
]

export default CRUD6Routes

export {
    CRUD6Routes
}
