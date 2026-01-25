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
      path: '/teacher/dashboard',
      name: 'TeacherDashboard',
      component: () => import('@/views/TeacherDashboard.vue'),
      meta: { requiresAuth: true, requiresTeacher: true }
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
      meta: { requiresAuth: true, requiresModule: 'institution' }
    },
    {
      path: '/student',
      name: 'Student',
      component: () => import('@/views/Student.vue'),
      meta: { requiresAuth: true, requiresModule: 'student' }
    },
    {
      path: '/teacher',
      name: 'Teacher',
      component: () => import('@/views/Teacher.vue'),
      meta: { requiresAuth: true, requiresModule: 'teacher' }
    },
    {
      path: '/module-access',
      name: 'ModuleAccess',
      component: () => import('@/views/ModuleAccess.vue'),
      meta: { requiresAuth: true, requiresInstitutionAdmin: true }
    },
    {
      path: '/facility',
      name: 'Facility',
      component: () => import('@/views/Facility.vue'),
      meta: { requiresAuth: true, requiresModule: 'facility' }
    },
    {
      path: '/class',
      name: 'Class',
      component: () => import('@/views/Class.vue'),
      meta: { requiresAuth: true, requiresModule: 'class' }
    },
    {
      path: '/report',
      name: 'Report',
      component: () => import('@/views/Report.vue'),
      meta: { requiresAuth: true, requiresModule: 'report' }
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
      meta: { requiresAuth: true, requiresModule: 'correspondence' }
    },
    {
      path: '/inventory',
      name: 'Inventory',
      component: () => import('@/views/Inventory.vue'),
      meta: { requiresAuth: true, requiresModule: 'inventory' }
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

const getDefaultRoute = (role) => {
  if (role === 'super_admin') {
    return '/super-admin/dashboard'
  }
  if (role === 'teacher') {
    return '/teacher/dashboard'
  }
  return '/dashboard'
}

const hasModuleAccess = (user, moduleKey) => {
  if (!user) return false
  if (user.role === 'super_admin' || user.role === 'admin' || user.role === 'institution_admin') {
    return true
  }
  return (user.permissions || []).includes(moduleKey)
}

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Prevent infinite redirects
  if (to.path === from.path) {
    next()
    return
  }
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    // Redirect based on user role
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch (error) {
        // If fetchUser fails, user is not actually authenticated
        authStore.isAuthenticated = false
        authStore.user = null
        next('/login')
        return
      }
    }

    const defaultRoute = getDefaultRoute(authStore.user?.role)
    // Prevent redirect to same route
    if (to.path !== defaultRoute) {
      next(defaultRoute)
    } else {
      next()
    }
  } else if (to.meta.requiresSuperAdmin) {
    // Ensure user data is loaded
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch (error) {
        authStore.isAuthenticated = false
        authStore.user = null
        next('/login')
        return
      }
    }
    
    if (authStore.user?.role !== 'super_admin') {
      const defaultRoute = getDefaultRoute(authStore.user?.role)
      if (to.path !== defaultRoute) {
        next(defaultRoute)
      } else {
        next()
      }
    } else {
      next()
    }
  } else if (to.meta.requiresTeacher) {
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch (error) {
        authStore.isAuthenticated = false
        authStore.user = null
        next('/login')
        return
      }
    }

    if (authStore.user?.role !== 'teacher') {
      const defaultRoute = getDefaultRoute(authStore.user?.role)
      if (to.path !== defaultRoute) {
        next(defaultRoute)
      } else {
        next()
      }
    } else {
      next()
    }
  } else if (to.meta.requiresInstitutionAdmin) {
    // Ensure user data is loaded
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch (error) {
        authStore.isAuthenticated = false
        authStore.user = null
        next('/login')
        return
      }
    }
    
    if (authStore.user?.role === 'super_admin') {
      if (to.path !== '/super-admin/dashboard') {
        next('/super-admin/dashboard')
      } else {
        next()
      }
    } else if (authStore.user?.role === 'teacher') {
      if (to.path !== '/teacher/dashboard') {
        next('/teacher/dashboard')
      } else {
        next()
      }
    } else if (authStore.user?.role === 'institution_admin' || authStore.user?.role === 'admin') {
      next()
    } else {
      const defaultRoute = getDefaultRoute(authStore.user?.role)
      if (to.path !== defaultRoute) {
        next(defaultRoute)
      } else {
        next()
      }
    }
  } else if (to.meta.requiresModule) {
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch (error) {
        authStore.isAuthenticated = false
        authStore.user = null
        next('/login')
        return
      }
    }

    if (!hasModuleAccess(authStore.user, to.meta.requiresModule)) {
      const defaultRoute = getDefaultRoute(authStore.user?.role)
      if (to.path !== defaultRoute) {
        next(defaultRoute)
      } else {
        next()
      }
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router
