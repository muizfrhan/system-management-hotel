import api from './api'

export const authApi = {
    csrfCookie: () => api.get('/sanctum/csrf-cookie', { baseURL: '' }),
    login: (email, password) => api.post('/auth/login', { email, password }),
    logout: () => api.post('/auth/logout'),
    getUser: () => api.get('/auth/user'),
}

export default authApi
