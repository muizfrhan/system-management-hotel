import api from './api'

export const guestApi = {
    getAll: (rolePrefix = '/admin') => api.get(`${rolePrefix}/guests`),
    get: (id, rolePrefix = '/admin') => api.get(`${rolePrefix}/guests/${id}`),
}

export default guestApi
