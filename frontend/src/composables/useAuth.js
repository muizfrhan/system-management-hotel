import { useAuthStore } from '../stores/authStore'

export function useAuth() {
    const store = useAuthStore()

    return {
        user: store.user,
        isAuthenticated: store.isAuthenticated,
        userRole: store.userRole,
        isAdmin: store.isAdmin,
        isReceptionist: store.isReceptionist,
        isHousekeeper: store.isHousekeeper,
        loading: store.loading,
        error: store.error,
        login: store.login,
        logout: store.logout,
        fetchUser: store.fetchUser,
        rolePrefix: store.isAdmin ? '/admin' : store.isReceptionist ? '/receptionist' : '/housekeeper',
    }
}

export default useAuth
