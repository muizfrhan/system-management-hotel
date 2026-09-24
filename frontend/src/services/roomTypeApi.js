import api from './api'

const prefix = (role) => role === 'receptionist' ? '/receptionist' : '/admin'

export const roomTypeApi = {
    getAll: () => api.get('/admin/room-types'),
    get: (id) => api.get(`/admin/room-types/${id}`),
    create: (data) => api.post('/admin/room-types', data),
    update: (id, data) => api.put(`/admin/room-types/${id}`, data),
    delete: (id) => api.delete(`/admin/room-types/${id}`),
}

export default roomTypeApi
