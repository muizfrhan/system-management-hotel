import api from './api'

export const reportApi = {
    get: (params = {}) => api.get('/admin/reports', { params }),
}

export default reportApi
