import api from './api'

export const staffApi = {
    getAll: () => api.get('/admin/staff'),
    create: (data) => api.post('/admin/staff', data),
    update: (id, data) => api.put(`/admin/staff/${id}`, data),
    delete: (id) => api.delete(`/admin/staff/${id}`),
}

export default staffApi
