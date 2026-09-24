import api from './api'

export const paymentApi = {
    getAll: (rolePrefix = '/admin') => api.get(`${rolePrefix}/payments`),
    create: (data, rolePrefix = '/admin') => api.post(`${rolePrefix}/payments`, data),
    invoice: (id, rolePrefix = '/admin') => api.get(`${rolePrefix}/payments/${id}/invoice`),
    invoicePdfUrl: (id, rolePrefix = '/admin') => `/api/v1${rolePrefix}/payments/${id}/invoice/pdf`,
}

export default paymentApi
