import api from './api'

export const facilityApi = {
    getAll: () => api.get('/admin/facilities'),
    create: (data) => api.post('/admin/facilities', data),
    update: (id, data) => api.put(`/admin/facilities/${id}`, data),
    delete: (id) => api.delete(`/admin/facilities/${id}`),
}

export default facilityApi
