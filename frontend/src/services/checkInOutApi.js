import api from './api'

export const checkInOutApi = {
    getAll: (rolePrefix = '/admin') => api.get(`${rolePrefix}/check-in-out`),
    checkIn: (id, rolePrefix = '/admin') => api.put(`${rolePrefix}/check-in-out/${id}/check-in`),
    checkOut: (id, rolePrefix = '/admin') => api.put(`${rolePrefix}/check-in-out/${id}/check-out`),
}

export default checkInOutApi
