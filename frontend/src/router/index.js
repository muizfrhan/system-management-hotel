import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const roleHomeRoutes = {
    admin: '/admin/dashboard',
    receptionist: '/receptionist/dashboard',
    housekeeper: '/housekeeper/housekeeping',
}

const adminViews = [
    { path: 'dashboard', component: () => import('../views/admin/DashboardView.vue'), meta: { roles: ['admin'] } },
    { path: 'room-types', component: () => import('../views/admin/RoomTypesView.vue'), meta: { roles: ['admin'] } },
    { path: 'rooms', component: () => import('../views/admin/RoomsView.vue'), meta: { roles: ['admin'] } },
    { path: 'facilities', component: () => import('../views/admin/FacilitiesView.vue'), meta: { roles: ['admin'] } },
    { path: 'booking-online', component: () => import('../views/admin/BookingOnlineView.vue'), meta: { roles: ['admin'] } },
    { path: 'reservations', component: () => import('../views/admin/ReservationsView.vue'), meta: { roles: ['admin'] } },
    { path: 'check-in-out', component: () => import('../views/admin/CheckInOutView.vue'), meta: { roles: ['admin'] } },
    { path: 'payments', component: () => import('../views/admin/PaymentsView.vue'), meta: { roles: ['admin'] } },
    { path: 'guests', component: () => import('../views/admin/GuestsView.vue'), meta: { roles: ['admin'] } },
    { path: 'housekeeping', component: () => import('../views/admin/HousekeepingView.vue'), meta: { roles: ['admin'] } },
    { path: 'staff', component: () => import('../views/admin/StaffView.vue'), meta: { roles: ['admin'] } },
    { path: 'reports', component: () => import('../views/admin/ReportsView.vue'), meta: { roles: ['admin'] } },
    { path: 'settings', component: () => import('../views/admin/SettingsView.vue'), meta: { roles: ['admin'] } },
]

const receptionistViews = [
    { path: 'dashboard', component: () => import('../views/receptionist/DashboardView.vue'), meta: { roles: ['receptionist'] } },
    { path: 'rooms', component: () => import('../views/receptionist/RoomsView.vue'), meta: { roles: ['receptionist'] } },
    { path: 'booking-online', component: () => import('../views/admin/BookingOnlineView.vue'), meta: { roles: ['receptionist'] } },
    { path: 'reservations', component: () => import('../views/admin/ReservationsView.vue'), meta: { roles: ['receptionist'] } },
    { path: 'check-in-out', component: () => import('../views/admin/CheckInOutView.vue'), meta: { roles: ['receptionist'] } },
    { path: 'payments', component: () => import('../views/admin/PaymentsView.vue'), meta: { roles: ['receptionist'] } },
    { path: 'guests', component: () => import('../views/admin/GuestsView.vue'), meta: { roles: ['receptionist'] } },
]

const housekeeperViews = [
    { path: 'housekeeping', component: () => import('../views/admin/HousekeepingView.vue'), meta: { roles: ['housekeeper'] } },
]

const routes = [
    {
        path: '/',
        component: () => import('../views/landing/HomeView.vue'),
        meta: { public: true, seo: 'index, follow' },
    },
    {
        path: '/rooms',
        component: () => import('../views/landing/RoomListView.vue'),
        meta: { public: true, seo: 'index, follow' },
    },
    {
        path: '/rooms/:id',
        component: () => import('../views/landing/RoomDetailView.vue'),
        meta: { public: true },
    },
    {
        path: '/booking/success/:code',
        component: () => import('../views/landing/BookingSuccessView.vue'),
        meta: { public: true },
    },
    {
        path: '/booking/success',
        redirect: '/',
    },
    {
        path: '/booking/:roomTypeId',
        component: () => import('../views/landing/BookingView.vue'),
        meta: { public: true },
    },
    {
        path: '/track',
        component: () => import('../views/landing/TrackBookingView.vue'),
        meta: { public: true },
    },
    {
        path: '/login',
        component: () => import('../views/auth/LoginView.vue'),
        meta: { guest: true },
    },
    {
        path: '/admin',
        component: () => import('../components/layout/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/admin/dashboard' },
            ...adminViews,
        ],
    },
    {
        path: '/receptionist',
        component: () => import('../components/layout/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/receptionist/dashboard' },
            ...receptionistViews,
        ],
    },
    {
        path: '/housekeeper',
        component: () => import('../components/layout/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/housekeeper/housekeeping' },
            ...housekeeperViews,
        ],
    },
    {
        path: '/403',
        component: () => import('../views/ForbiddenView.vue'),
        meta: { errorPage: true },
    },
    {
        path: '/500',
        component: () => import('../views/ServerErrorView.vue'),
        meta: { errorPage: true },
    },
    {
        path: '/:pathMatch(.*)*',
        component: () => import('../views/landing/NotFoundView.vue'),
        meta: { errorPage: true },
    },
]

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition
        if (to.hash) {
            const reducedMotion = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
            return new Promise((resolve) => {
                window.setTimeout(() => {
                    const target = document.getElementById(to.hash.slice(1))
                    if (!target) {
                        resolve({ top: 0 })
                        return
                    }
                    target.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' })
                    resolve(false)
                }, reducedMotion ? 0 : 280)
            })
        }
        return { top: 0 }
    },
})

function isKnownRole(role) {
    return role === 'admin' || role === 'receptionist' || role === 'housekeeper'
}

function getRoleHome(role) {
    return isKnownRole(role) ? roleHomeRoutes[role] : '/403'
}

function isInternalPath(value) {
    return typeof value === 'string' && value.startsWith('/') && !value.startsWith('//') && !value.includes('\\')
}

function getSafeRedirect(value, role) {
    if (!isInternalPath(value)) return null

    const target = router.resolve(value)
    if (!target.matched.length) return null
    if (target.matched.some((record) => record.meta.errorPage || record.meta.guest)) return null

    const requiresAuth = target.matched.some((record) => record.meta.requiresAuth)
    if (!requiresAuth) return target.fullPath

    const allowedRoles = [...new Set(target.matched.flatMap((record) => record.meta.roles || []))]
    return allowedRoles.includes(role) ? target.fullPath : null
}

router.beforeEach(async (to) => {
    if (to.meta.public) return true

    const auth = useAuthStore()
    if (!auth.initialized) await auth.initialize()

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return {
            path: '/login',
            query: to.fullPath === '/login' ? {} : { redirect: to.fullPath },
        }
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return getSafeRedirect(to.query.redirect, auth.userRole) || getRoleHome(auth.userRole)
    }

    if (to.meta.requiresAuth && !isKnownRole(auth.userRole)) return '/403'

    if (to.meta.roles && !to.meta.roles.includes(auth.userRole)) return '/403'

    return true
})

function setMetaContent(selector, content) {
    let element = document.head.querySelector(selector)
    if (!element) {
        element = document.createElement('meta')
        const [key, value] = selector.match(/(?:property|name)="([^"]+)"/).slice(1)
        element.setAttribute(key, value)
        document.head.appendChild(element)
    }
    element.setAttribute('content', content)
}

function setRouteSeo(to) {
    const indexable = to.meta.seo === 'index, follow'
    setMetaContent('meta[name="robots"]', indexable ? 'index, follow' : 'noindex, nofollow')
    if (to.path === '/rooms') {
        const description = 'Lihat detail tipe kamar, fasilitas, harga, dan ketersediaan hotel secara online.'
        setMetaContent('meta[name="description"]', description)
        setMetaContent('meta[property="og:description"]', description)
    }

    let canonical = document.head.querySelector('link[rel="canonical"]')
    if (!indexable) {
        canonical?.remove()
        document.head.querySelector('meta[property="og:url"]')?.remove()
        return
    }

    if (!canonical) {
        canonical = document.createElement('link')
        canonical.setAttribute('rel', 'canonical')
        document.head.appendChild(canonical)
    }
    const baseUrl = new URL(import.meta.env.BASE_URL || '/', window.location.origin)
    const url = new URL(to.path.replace(/^\//, ''), baseUrl).href
    canonical.setAttribute('href', url)
    setMetaContent('meta[property="og:url"]', url)
}

const pageTitles = {
  '/': 'Beranda',
  '/rooms': 'Pilihan Kamar',
  '/track': 'Lacak Reservasi',
  '/login': 'Login',
  '/403': 'Akses Ditolak',
  '/500': 'Kesalahan Server',
}

router.afterEach((to) => {
  if (typeof document === 'undefined') return
  setRouteSeo(to)
  if (to.path === '/') return
  if (to.path.startsWith('/booking/success/')) {
    document.title = 'Pemesanan Berhasil — Lokanata Hotel'
    return
  }
  if (to.path.startsWith('/booking/')) {
    document.title = 'Pemesanan Kamar — Lokanata Hotel'
    return
  }
  const segment = to.path.split('/').filter(Boolean).pop()
  const labels = {
    dashboard: 'Dasbor',
    'room-types': 'Tipe Kamar',
    rooms: 'Kamar',
    facilities: 'Fasilitas',
    'booking-online': 'Booking Online',
    reservations: 'Reservasi',
    'check-in-out': 'Check In/Out',
    payments: 'Pembayaran',
    guests: 'Data Tamu',
    housekeeping: 'Tata Graha',
    staff: 'Manajemen Staf',
    reports: 'Laporan',
    settings: 'Pengaturan',
  }
  document.title = `${pageTitles[to.path] || labels[segment] || 'Lokanata Hotel'} — Lokanata Hotel`
})

export default router
