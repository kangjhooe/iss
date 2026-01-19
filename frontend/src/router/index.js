import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'Home',
      component: () => import('@/views/Home.vue')
    },
    {
      path: '/login',
      name: 'Login',
      component: () => import('@/views/Login.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/register',
      name: 'Register',
      component: () => import('@/views/Register.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/dashboard',
      name: 'Dashboard',
      component: () => import('@/views/Dashboard.vue'),
      meta: { requiresAuth: true, requiresInstitutionAdmin: true }
    },
    {
      path: '/super-admin/dashboard',
      name: 'SuperAdminDashboard',
      component: () => import('@/views/SuperAdminDashboard.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/institution',
      name: 'Institution',
      component: () => import('@/views/Institution.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/student',
      name: 'Student',
      component: () => import('@/views/Student.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/teacher',
      name: 'Teacher',
      component: () => import('@/views/Teacher.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/facility',
      name: 'Facility',
      component: () => import('@/views/Facility.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/class',
      name: 'Class',
      component: () => import('@/views/Class.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/report',
      name: 'Report',
      component: () => import('@/views/Report.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/academic-year',
      name: 'AcademicYear',
      component: () => import('@/views/AcademicYear.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/institution-change-requests',
      name: 'InstitutionChangeRequests',
      component: () => import('@/views/InstitutionChangeRequests.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/correspondence',
      name: 'Correspondence',
      component: () => import('@/views/Correspondence.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/forgot-password',
      name: 'ForgotPassword',
      component: () => import('@/views/ForgotPassword.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/reset-password',
      name: 'ResetPassword',
      component: () => import('@/views/ResetPassword.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/verify-email',
      name: 'VerifyEmail',
      component: () => import('@/views/VerifyEmail.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/semester',
      name: 'Semester',
      component: () => import('@/views/Semester.vue'),
      meta: { requiresAuth: true }
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    // Redirect based on user role
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch (error) {
        next('/login')
        return
      }
    }
    
    if (authStore.user?.role === 'super_admin') {
      next('/super-admin/dashboard')
    } else {
      next('/dashboard')
    }
  } else if (to.meta.requiresSuperAdmin) {
    // Ensure user data is loaded
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch (error) {
        next('/login')
        return
      }
    }
    
    if (authStore.user?.role !== 'super_admin') {
      next('/dashboard')
    } else {
      next()
    }
  } else if (to.meta.requiresInstitutionAdmin) {
    // Ensure user data is loaded
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch (error) {
        next('/login')
        return
      }
    }
    
    if (authStore.user?.role === 'super_admin') {
      next('/super-admin/dashboard')
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router
