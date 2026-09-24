import api from './api'

export const settingApi = {
    get: () => api.get('/admin/settings'),
    update: (data) => api.put('/admin/settings', data),
}

export default settingApi
