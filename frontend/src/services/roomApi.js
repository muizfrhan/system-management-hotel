import api from './api'

export const roomApi = {
    getAll: (params = {}) => api.get('/admin/rooms', { params }),
    create: (data) => api.post('/admin/rooms', data),
    update: (id, data) => api.put(`/admin/rooms/${id}`, data),
    updateStatus: (id, status) => api.patch(`/admin/rooms/${id}/status`, { status }),
    delete: (id) => api.delete(`/admin/rooms/${id}`),
}

export default roomApi
