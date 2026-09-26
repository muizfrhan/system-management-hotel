import axios from 'axios'

let loginExpiryStarted = false
let csrfRequest = null
const stateChangingMethods = new Set(['post', 'put', 'patch', 'delete'])

function clearStoredUser() {
    try {
        localStorage.removeItem('user')
    } catch {}
}

function isLoginPage(pathname) {
    return pathname.replace(/\/+$/, '') === '/login'
}

function hasCsrfCookie() {
    return document.cookie.split(';').some((cookie) => cookie.trim().startsWith('XSRF-TOKEN='))
}

export function ensureCsrfCookie(force = false) {
    if (!force && hasCsrfCookie()) return Promise.resolve()
    if (!csrfRequest) {
        csrfRequest = axios.get('/sanctum/csrf-cookie', { withCredentials: true, timeout: 15000 })
            .finally(() => {
                csrfRequest = null
            })
    }
    return csrfRequest
}

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
    timeout: 15000,
})

api.interceptors.request.use(async (config) => {
    if (stateChangingMethods.has(config.method?.toLowerCase()) && !config.skipCsrf) {
        await ensureCsrfCookie()
    }
    return config
})

api.interceptors.response.use(
    (response) => {
        if (response.config.url === '/auth/login') loginExpiryStarted = false
        return response
    },
    async (error) => {
        if (error.response?.status === 419 && !error.config?._csrfRetried) {
            error.config._csrfRetried = true
            await ensureCsrfCookie(true)
            return api.request(error.config)
        }

        if (error.response?.status === 401 && !loginExpiryStarted) {
            loginExpiryStarted = true
            clearStoredUser()

            const currentPath = `${window.location.pathname}${window.location.search}${window.location.hash}`
            const protectedPath = /^\/(admin|receptionist|housekeeper)(?:\/|$)/.test(window.location.pathname)
            if (protectedPath && !isLoginPage(window.location.pathname)) {
                const query = new URLSearchParams({ reason: 'session-expired' })
                if (currentPath.startsWith('/') && !currentPath.startsWith('//') && !currentPath.includes('\\')) {
                    query.set('redirect', currentPath)
                }
                window.location.replace(`/login?${query.toString()}`)
            }
        }
        return Promise.reject(error)
    }
)

export default api
