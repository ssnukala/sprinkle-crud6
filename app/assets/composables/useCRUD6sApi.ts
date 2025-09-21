import { ref } from 'vue'
import axios from 'axios'
import { type AlertInterface } from '@userfrosting/sprinkle-core/interfaces'
import type { CRUD6SprunjerResponse } from '../interfaces'
import type { CRUD6Interface } from '../interfaces'

/**
 * API used to fetch a list of CRUD6 records for a specific model.
 *
 * This interface is tied to the `CRUD6SprunjeAction` API, accessed at the
 * GET `/api/crud6/{model}` endpoint and the `CRUD6SprunjerResponse` Typescript
 * interface.
 *
 * This composable can be used to access a list of records, for select purpose
 * for example. While it uses the Sprunje on the backend, it doesn't filter
 * nor sort the data. The Sprunje should be used directly for that.
 *
 * NOTE: This list respects access controls based on the model's permissions.
 */
export function useCRUD6sApi(model: string) {
    const loading = ref(false)
    const error = ref<AlertInterface | null>()
    const crud6Rows = ref<CRUD6Interface[]>([])

    async function updateCRUD6s() {
        loading.value = true
        error.value = null

        await axios
            .get<CRUD6SprunjerResponse>(`/api/crud6/${model}`)
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
