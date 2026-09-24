import { ref, onMounted } from 'vue'
import paymentApi from '../services/paymentApi'

export function usePayments(rolePrefix = '/admin') {
    const payments = ref([])
    const loading = ref(false)

    async function fetchAll() {
        loading.value = true
        try {
            const res = await paymentApi.getAll(rolePrefix)
            payments.value = res.data.data || res.data
        } finally {
            loading.value = false
        }
    }

    onMounted(fetchAll)

    return { payments, loading, fetchAll }
}

export default usePayments
