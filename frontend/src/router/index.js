import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const adminViews = [
    { path: 'dashboard', component: () => import('../views/admin/DashboardView.vue') },
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
    },
    {
        path: '/rooms',
        component: () => import('../views/landing/RoomListView.vue'),
    },
    {
        path: '/rooms/:id',
        component: () => import('../views/landing/RoomDetailView.vue'),
    },
    {
        path: '/booking/:roomTypeId',
        component: () => import('../views/landing/BookingView.vue'),
    },
    {
        path: '/booking/success/:code',
        component: () => import('../views/landing/BookingSuccessView.vue'),
    },
    {
        path: '/track',
        component: () => import('../views/landing/TrackBookingView.vue'),
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
        children: adminViews,
    },
    {
        path: '/receptionist',
        component: () => import('../components/layout/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/receptionist/booking-online' },
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
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, from, next) => {
    const auth = useAuthStore()

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return next('/login')
    }

    if (to.meta.guest && auth.isAuthenticated) {
        if (auth.userRole === 'receptionist') return next('/receptionist/booking-online')
        if (auth.userRole === 'housekeeper') return next('/housekeeper/housekeeping')
        return next('/admin/dashboard')
    }

    if (to.meta.roles && !to.meta.roles.includes(auth.userRole)) {
        if (auth.userRole === 'receptionist') return next('/receptionist/booking-online')
        if (auth.userRole === 'housekeeper') return next('/housekeeper/housekeeping')
        return next('/admin/dashboard')
    }

    next()
})

export default router
