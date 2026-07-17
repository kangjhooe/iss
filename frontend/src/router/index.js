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
      path: '/catatan-rilis',
      name: 'ReleaseNotes',
      component: () => import('@/views/ReleaseNotes.vue')
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
      path: '/daftar-ppdb',
      name: 'PpdbPublicRegister',
      component: () => import('@/views/PpdbPublicRegister.vue')
    },
    {
      path: '/:npsn/daftar-ppdb',
      name: 'PpdbPublicRegisterByNpsn',
      component: () => import('@/views/PpdbPublicRegister.vue')
    },
    {
      path: '/cek-hasil-ppdb',
      name: 'PpdbCheckResult',
      component: () => import('@/views/PpdbCheckResult.vue')
    },
    {
      path: '/lengkapi-berkas-ppdb',
      name: 'PpdbLengkapiBerkas',
      component: () => import('@/views/PpdbLengkapiBerkas.vue')
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
      path: '/teacher/profile',
      name: 'TeacherProfile',
      component: () => import('@/views/TeacherProfile.vue'),
      meta: { requiresAuth: true, requiresTeacher: true }
    },
    {
      path: '/teacher/poin',
      name: 'TeacherMyPoints',
      component: () => import('@/views/TeacherMyPoints.vue'),
      meta: { requiresAuth: true, requiresTeacher: true }
    },
    {
      path: '/teacher-appreciation',
      name: 'TeacherAppreciation',
      component: () => import('@/views/TeacherAppreciation.vue'),
      meta: { requiresAuth: true, requiresAnyModule: ['teacher_appreciation', 'teacher_violation_report'] }
    },
    {
      path: '/guru-piket',
      name: 'GuruPiket',
      component: () => import('@/views/GuruPiket.vue'),
      meta: { requiresAuth: true, requiresAnyModule: ['guru_piket', 'guru_piket_manage'] }
    },
    {
      path: '/super-admin/dashboard',
      name: 'SuperAdminDashboard',
      component: () => import('@/views/SuperAdminDashboard.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/super-admin/app-branding',
      name: 'AppBranding',
      component: () => import('@/views/AppBranding.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/super-admin/institution-admins',
      name: 'InstitutionAdmins',
      component: () => import('@/views/InstitutionAdmins.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/super-admin/onboard',
      name: 'SuperAdminOnboard',
      component: () => import('@/views/SuperAdminOnboard.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/super-admin/adoption',
      name: 'AdoptionMonitoring',
      component: () => import('@/views/AdoptionMonitoring.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/super-admin/broadcasts',
      name: 'BroadcastAnnouncements',
      component: () => import('@/views/BroadcastAnnouncements.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/super-admin/catatan-rilis',
      name: 'SuperAdminReleaseNotes',
      component: () => import('@/views/SuperAdminReleaseNotes.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/super-admin/templates',
      name: 'SuperAdminTemplates',
      component: () => import('@/views/SuperAdminTemplates.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/super-admin/reports',
      name: 'AggregateReport',
      component: () => import('@/views/AggregateReport.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/super-admin/system-settings',
      name: 'SystemSettings',
      component: () => import('@/views/SystemSettings.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/student/dashboard',
      name: 'StudentDashboard',
      component: () => import('@/views/StudentDashboard.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/jadwal',
      name: 'StudentSchedule',
      component: () => import('@/views/StudentSchedule.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/nilai',
      name: 'StudentGrades',
      component: () => import('@/views/StudentGrades.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/pelanggaran-prestasi',
      name: 'StudentViolations',
      component: () => import('@/views/StudentViolations.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/konseling',
      name: 'StudentCounseling',
      component: () => import('@/views/StudentCounseling.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/ekstrakurikuler',
      name: 'StudentExtracurricular',
      component: () => import('@/views/StudentExtracurricular.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/poin',
      name: 'StudentPoints',
      component: () => import('@/views/StudentPoints.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/profil',
      name: 'StudentProfile',
      component: () => import('@/views/StudentProfile.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/permintaan-perubahan',
      name: 'StudentChangeRequests',
      component: () => import('@/views/StudentChangeRequests.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/absensi',
      name: 'StudentAttendance',
      component: () => import('@/views/StudentAttendance.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
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
      path: '/student/:id/buku-induk',
      name: 'BukuInduk',
      component: () => import('@/views/BukuInduk.vue'),
      meta: { requiresAuth: true, requiresModule: 'student' }
    },
    {
      path: '/student-mutation',
      name: 'StudentMutation',
      component: () => import('@/views/StudentMutation.vue'),
      meta: { requiresAuth: true, requiresModule: 'student' }
    },
    {
      path: '/alumni',
      name: 'Alumni',
      component: () => import('@/views/Alumni.vue'),
      meta: { requiresAuth: true, requiresModule: 'student' }
    },
    {
      path: '/luluskan-siswa',
      name: 'LuluskanSiswa',
      component: () => import('@/views/LuluskanSiswa.vue'),
      meta: { requiresAuth: true, requiresModule: 'student' }
    },
    {
      path: '/naik-kelas',
      name: 'NaikKelas',
      component: () => import('@/views/NaikKelas.vue'),
      meta: { requiresAuth: true, requiresModule: 'student' }
    },
    {
      path: '/violation',
      name: 'Violation',
      component: () => import('../views/Violation.vue'),
      meta: { requiresAuth: true, requiresModule: 'violation' }
    },
    {
      path: '/counseling',
      name: 'Counseling',
      component: () => import('../views/Counseling.vue'),
      meta: { requiresAuth: true, requiresModule: 'counseling' }
    },
    {
      path: '/laporan-bk',
      name: 'LaporanBk',
      component: () => import('../views/LaporanBk.vue'),
      meta: { requiresAuth: true, requiresAnyModule: ['violation', 'counseling', 'bk_report'] }
    },
    {
      path: '/extracurricular',
      name: 'Extracurricular',
      component: () => import('../views/Extracurricular.vue'),
      meta: { requiresAuth: true, requiresModule: 'extracurricular', allowExtracurricularSupervisor: true }
    },
    {
      path: '/extracurricular/:id',
      name: 'ExtracurricularDetail',
      component: () => import('../views/ExtracurricularDetail.vue'),
      meta: { requiresAuth: true, requiresModule: 'extracurricular', allowExtracurricularSupervisor: true }
    },
    {
      path: '/subject',
      name: 'Subject',
      component: () => import('@/views/Subject.vue'),
      meta: { requiresAuth: true, requiresModule: 'schedule' }
    },
    {
      path: '/lesson-schedule',
      name: 'LessonSchedule',
      component: () => import('@/views/LessonSchedule.vue'),
      meta: { requiresAuth: true, requiresModule: 'schedule' }
    },
    {
      path: '/teaching-journal',
      name: 'TeachingJournal',
      component: () => import('@/views/TeachingJournal.vue'),
      meta: { requiresAuth: true, requiresModule: 'teaching_journal' }
    },
    {
      path: '/attendance/student',
      name: 'AttendanceStudent',
      component: () => import('@/views/AttendanceStudent.vue'),
      meta: { requiresAuth: true, requiresModule: 'teaching_journal' }
    },
    {
      path: '/attendance/employee',
      name: 'AttendanceEmployee',
      component: () => import('@/views/AttendanceEmployee.vue'),
      meta: { requiresAuth: true, requiresModule: 'attendance' }
    },
    {
      path: '/qr-attendance/scan',
      name: 'QrAttendanceScan',
      component: () => import('@/views/QrAttendanceScan.vue'),
      meta: { requiresAuth: true, requiresModule: 'attendance' }
    },
    {
      path: '/qr-attendance/generate',
      name: 'QrCodeGenerate',
      component: () => import('@/views/QrCodeGenerate.vue'),
      meta: { requiresAuth: true, requiresModule: 'attendance' }
    },
    {
      path: '/grade-book',
      name: 'GradeBook',
      component: () => import('@/views/GradeBook.vue'),
      meta: { requiresAuth: true, requiresModule: 'grade_book' }
    },
    {
      path: '/raport',
      name: 'Raport',
      component: () => import('@/views/Raport.vue'),
      meta: { requiresAuth: true, requiresModule: 'grade_book' }
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
      path: '/lab',
      name: 'Lab',
      component: () => import('@/views/Lab.vue'),
      meta: { requiresAuth: true, requiresModule: 'facility', allowLabResponsible: true }
    },
    {
      path: '/lab/:id',
      name: 'LabDetail',
      component: () => import('@/views/LabDetail.vue'),
      meta: { requiresAuth: true, requiresModule: 'facility', allowLabResponsible: true }
    },
    {
      path: '/lab-booking',
      name: 'LabBookingRequest',
      component: () => import('@/views/LabBookingRequest.vue'),
      meta: { requiresAuth: true }
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
      path: '/student-change-requests',
      name: 'StudentChangeRequestsAdmin',
      component: () => import('@/views/StudentChangeRequestsAdmin.vue'),
      meta: { requiresAuth: true, requiresModule: 'student' }
    },
    {
      path: '/teacher-change-requests',
      name: 'TeacherChangeRequestsAdmin',
      component: () => import('@/views/TeacherChangeRequestsAdmin.vue'),
      meta: { requiresAuth: true, requiresModule: 'teacher' }
    },
    {
      path: '/correspondence',
      name: 'Correspondence',
      component: () => import('@/views/Surat/Index.vue'),
      meta: { requiresAuth: true, requiresModule: 'correspondence' }
    },
    {
      path: '/correspondence/templates',
      name: 'CorrespondenceTemplates',
      component: () => import('@/views/Surat/Template/Index.vue'),
      meta: { requiresAuth: true, requiresModule: 'correspondence' }
    },
    {
      path: '/correspondence/kop',
      name: 'CorrespondenceKop',
      component: () => import('@/views/Surat/Kop/Index.vue'),
      meta: { requiresAuth: true, requiresModule: 'correspondence' }
    },
    {
      path: '/correspondence/tanda-tangan',
      name: 'CorrespondenceTandaTangan',
      component: () => import('@/views/Surat/TandaTangan/Index.vue'),
      meta: { requiresAuth: true, requiresModule: 'correspondence' }
    },
    {
      path: '/correspondence/workflow',
      name: 'CorrespondenceWorkflow',
      component: () => import('@/views/Correspondence.vue'),
      meta: { requiresAuth: true, requiresModule: 'correspondence' }
    },
    {
      path: '/digital-archive',
      name: 'DigitalArchive',
      component: () => import('@/views/DigitalArchive.vue'),
      meta: { requiresAuth: true, requiresModule: 'digital_archive' }
    },
    {
      path: '/buku-tamu',
      name: 'BukuTamu',
      component: () => import('@/views/BukuTamu.vue'),
      meta: { requiresAuth: true, requiresModule: 'guest_book' }
    },
    {
      path: '/pengambilan-ijazah',
      name: 'DocumentPickup',
      component: () => import('@/views/DocumentPickup.vue'),
      meta: { requiresAuth: true, requiresModule: 'document_pickup' }
    },
    {
      path: '/inventory',
      name: 'Inventory',
      component: () => import('@/views/Inventory.vue'),
      meta: { requiresAuth: true, requiresModule: 'inventory' }
    },
    {
      path: '/library',
      name: 'Library',
      component: () => import('@/views/Library.vue'),
      meta: { requiresAuth: true, requiresModule: 'library' }
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
    },
    {
      path: '/notifications',
      name: 'Notifications',
      component: () => import('@/views/Notifications.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/pengaturan-akun',
      name: 'AccountSettings',
      component: () => import('@/views/AccountSettings.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/audit-log',
      name: 'AuditLog',
      component: () => import('@/views/AuditLog.vue'),
      meta: { requiresAuth: true, requiresAuditLog: true }
    },
    {
      path: '/ppdb',
      name: 'Ppdb',
      component: () => import('@/views/Ppdb.vue'),
      meta: { requiresAuth: true, requiresModule: 'ppdb' }
    },
    {
      path: '/ujian-online',
      name: 'OnlineExam',
      redirect: { name: 'OnlineExamList' }
    },
    {
      path: '/ujian-online/exams',
      name: 'OnlineExamList',
      component: () => import('@/views/OnlineExam/ExamList.vue'),
      meta: { requiresAuth: true, requiresModule: 'online_exam' }
    },
    {
      path: '/ujian-online/exams/buat',
      name: 'OnlineExamCreate',
      component: () => import('@/views/OnlineExam/ExamForm.vue'),
      meta: { requiresAuth: true, requiresModule: 'online_exam' }
    },
    {
      path: '/ujian-online/exams/:code',
      name: 'OnlineExamDetail',
      component: () => import('@/views/OnlineExam/ExamDetail.vue'),
      meta: { requiresAuth: true, requiresModule: 'online_exam' }
    },
    {
      path: '/ujian-online/exams/:code/edit',
      name: 'OnlineExamEdit',
      component: () => import('@/views/OnlineExam/ExamForm.vue'),
      meta: { requiresAuth: true, requiresModule: 'online_exam' }
    },
    {
      path: '/ujian-online/sesi',
      name: 'OnlineExamSessions',
      component: () => import('@/views/OnlineExam/SessionList.vue'),
      meta: { requiresAuth: true, requiresModule: 'online_exam' }
    },
    {
      path: '/ujian-online/sesi/:id',
      name: 'OnlineExamSessionDetail',
      component: () => import('@/views/OnlineExam/SessionDetail.vue'),
      meta: { requiresAuth: true, requiresModule: 'online_exam' }
    },
    {
      path: '/ujian-online/bank-soal',
      name: 'OnlineExamBank',
      component: () => import('@/views/OnlineExam/BankSoalList.vue'),
      meta: { requiresAuth: true, requiresModule: 'online_exam' }
    },
    {
      path: '/ujian-online/bank-soal/:bankId/soal',
      name: 'OnlineExamBankSoal',
      component: () => import('@/views/OnlineExam/QuestionBank.vue'),
      meta: { requiresAuth: true, requiresModule: 'online_exam' }
    },
    {
      path: '/ujian-online/bank-soal/:bankId/stimulus',
      name: 'OnlineExamBankStimulus',
      component: () => import('@/views/OnlineExam/StimulusList.vue'),
      meta: { requiresAuth: true, requiresModule: 'online_exam' }
    },
    {
      path: '/ujian-online/stimulus',
      redirect: '/ujian-online/bank-soal'
    },
    {
      path: '/ujian-ikuti',
      name: 'ExamTake',
      component: () => import('@/views/OnlineExam/ExamTake.vue')
    },
    {
      path: '/:npsn',
      name: 'SchoolPublic',
      component: () => import('@/views/SchoolPublic.vue')
    }
  ]
})

const getDefaultRoute = (role) => {
  if (role === 'super_admin') {
    return '/super-admin/dashboard'
  }
  if (role === 'teacher' || role === 'staff') {
    return '/teacher/dashboard'
  }
  if (role === 'student') {
    return '/student/dashboard'
  }
  return '/dashboard'
}

const hasModuleAccess = (user, moduleKey) => {
  if (!user) return false
  if (user.role === 'super_admin' || user.role === 'admin' || user.role === 'institution_admin') {
    return true
  }
  if ((user.permissions || []).includes(moduleKey)) return true
  // Guru terjadwal piket boleh akses modul operasional (bukan manage)
  if (moduleKey === 'guru_piket' && (user.is_piket_scheduled || user.is_piket_on_duty)) {
    return true
  }
  return false
}

const hasAnyModuleAccess = (user, moduleKeys) => {
  if (!user || !Array.isArray(moduleKeys) || !moduleKeys.length) return false
  if (user.role === 'super_admin' || user.role === 'admin' || user.role === 'institution_admin') {
    return true
  }
  if (moduleKeys.some(k => hasModuleAccess(user, k))) return true
  if (moduleKeys.includes('guru_piket') && (user.is_piket_scheduled || user.is_piket_on_duty)) {
    return true
  }
  return false
}

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Prevent infinite redirects
  if (to.path === from.path) {
    next()
    return
  }

  // Redirect logged-in users from home to their dashboard
  if (to.path === '/' && authStore.isAuthenticated) {
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch {
        authStore.isAuthenticated = false
        authStore.user = null
        next()
        return
      }
    }
    const defaultRoute = getDefaultRoute(authStore.user?.role)
    next(defaultRoute)
    return
  }
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Token via httpOnly cookie: coba /me dulu; kalau ada cookie, fetchUser berhasil
    try {
      await authStore.fetchUser()
    } catch {
      // ignore
    }
    if (!authStore.isAuthenticated) {
      next('/login')
      return
    }
  }

  if (to.meta.requiresGuest && authStore.isAuthenticated) {
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

    if (authStore.user?.role !== 'teacher' && authStore.user?.role !== 'staff') {
      const defaultRoute = getDefaultRoute(authStore.user?.role)
      if (to.path !== defaultRoute) {
        next(defaultRoute)
      } else {
        next()
      }
    } else {
      next()
    }
  } else if (to.meta.requiresStudent) {
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

    if (authStore.user?.role !== 'student') {
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
    } else if (authStore.user?.role === 'teacher' || authStore.user?.role === 'staff') {
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
  } else if (to.meta.requiresAuditLog) {
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
    const role = authStore.user?.role
    const allowed = role === 'super_admin' || role === 'institution_admin' || role === 'admin'
    if (!allowed) {
      const defaultRoute = getDefaultRoute(role)
      next(defaultRoute)
    } else {
      next()
    }
  } else if (to.meta.requiresAnyModule) {
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

    if (!hasAnyModuleAccess(authStore.user, to.meta.requiresAnyModule)) {
      const defaultRoute = getDefaultRoute(authStore.user?.role)
      if (to.path !== defaultRoute) {
        next(defaultRoute)
      } else {
        next()
      }
    } else {
      next()
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
      if (to.meta.allowLabResponsible && authStore.user?.is_lab_responsible) {
        next()
        return
      }
      if (to.meta.allowExtracurricularSupervisor && authStore.user?.is_extracurricular_supervisor) {
        next()
        return
      }
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
