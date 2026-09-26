import api from './api'

export const bookingApi = {
    getOnline: (rolePrefix = '/admin') => api.get(`${rolePrefix}/booking-online`),
    confirm: (id, rolePrefix = '/admin') => api.put(`${rolePrefix}/booking-online/${id}/confirm`),
    reject: (id, rolePrefix = '/admin') => api.put(`${rolePrefix}/booking-online/${id}/reject`),
    submitGuest: (data, config) => api.post('/guest/booking', data, config),
}

export default bookingApi
