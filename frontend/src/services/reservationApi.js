import api from './api'

export const reservationApi = {
    getAll: (params = {}, rolePrefix = '/admin') => api.get(`${rolePrefix}/reservations`, { params }),
    get: (id, rolePrefix = '/admin') => api.get(`${rolePrefix}/reservations/${id}`),
    create: (data, rolePrefix = '/admin') => api.post(`${rolePrefix}/reservations`, data),
    update: (id, data, rolePrefix = '/admin') => api.put(`${rolePrefix}/reservations/${id}`, data),
    cancel: (id, rolePrefix = '/admin') => api.delete(`${rolePrefix}/reservations/${id}`),
    addCharge: (id, data, rolePrefix = '/admin') => api.post(`${rolePrefix}/reservations/${id}/charges`, data),
    removeCharge: (chargeId, rolePrefix = '/admin') => api.delete(`${rolePrefix}/charges/${chargeId}`),
}

export default reservationApi
