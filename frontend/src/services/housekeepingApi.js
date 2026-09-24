import api from './api'

export const housekeepingApi = {
    getAll: (rolePrefix = '/admin') => api.get(`${rolePrefix}/housekeeping`),
    markDone: (roomId, rolePrefix = '/admin') => api.patch(`${rolePrefix}/housekeeping/${roomId}/done`),
}

export default housekeepingApi
