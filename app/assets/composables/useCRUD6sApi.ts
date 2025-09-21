import { ref } from 'vue'
import axios from 'axios'
import { type AlertInterface } from '@userfrosting/sprinkle-core/interfaces'
import type { CRUD6SprunjerResponse } from '../interfaces'
import type { CRUD6Interface } from '../interfaces'

/**
 * API used to fetch a list of groups.
 *
 * This interface is tied to the `GroupsSprunjeAction` API, accessed at the
 * GET `/api/groups` endpoint and the `GroupsSprunjerResponse` Typescript
 * interface.
 *
 * This composable can be used to access a list of groups, for select purpose
 * for example. While it uses the Sprunjer on the backend, it doesn't filter
 * nor sort the data. The Sprunjer should be used directly for that.
 *
 * NOTE: This group list is not access controlled. It return all groups, so use
 * it wisely.
 */
export function useCRUD6sApi() {
    const loading = ref(false)
    const error = ref<AlertInterface | null>()
    const crud6Rows = ref<CRUD6Interface[]>([])

    async function updateCRUD6s() {
        loading.value = true
        error.value = null

        await axios
            .get<CRUD6SprunjerResponse>('/api/crud6')
            .then((response) => {
                crud6Rows.value = response.data.rows
            })
            .catch((err) => {
                error.value = err.response.data
            })
            .finally(() => {
                loading.value = false
            })
    }

    return { crud6Rows, error, loading, updateCRUD6s }
}
