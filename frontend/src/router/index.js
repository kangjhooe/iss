import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { getActiveInstitutionLevel, isVocationalLevel } from '@/utils/institution'
import { hasModuleAccess, hasAnyModuleAccess } from '@/utils/moduleAccess'
import { INVENTORY_NAV_ITEMS, INVENTORY_TAB_BY_ROUTE_NAME, canAccessInventoryTab } from '@/composables/inventory/inventoryRoutes'
import { applyRouteSeo } from '@/utils/seo'

const inventoryRoutes = INVENTORY_NAV_ITEMS.map((item) => ({
  path: item.path,
  name: item.routeName,
  component: () => import('@/views/Inventory.vue'),
  meta: {
    requiresAuth: true,
    requiresModule: 'inventory',
    allowLabResponsible: true,
    allowRoomResponsible: true,
    inventoryLabAllowed: item.roomScoped !== false,
    inventoryRoomAllowed: item.roomScoped !== false,
  },
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'Home',
      component: () => import('@/views/Home.vue'),
      meta: { index: true }
    },
    {
      path: '/catatan-rilis',
      name: 'ReleaseNotes',
      component: () => import('@/views/ReleaseNotes.vue'),
      meta: { index: true }
    },
    {
      path: '/panduan',
      name: 'Panduan',
      component: () => import('@/views/Panduan.vue'),
      meta: { index: true }
    },
    {
      path: '/panduan/admin',
      name: 'PanduanAdmin',
      component: () => import('@/views/PanduanAdmin.vue'),
      meta: { index: true }
    },
    {
      path: '/panduan/guru',
      name: 'PanduanGuru',
      component: () => import('@/views/PanduanGuru.vue'),
      meta: { index: true }
    },
    {
      path: '/panduan/siswa',
      name: 'PanduanSiswa',
      component: () => import('@/views/PanduanSiswa.vue'),
      meta: { index: true }
    },
    {
      path: '/panduan/orang-tua',
      name: 'PanduanOrangTua',
      component: () => import('@/views/PanduanOrangTua.vue'),
      meta: { index: true }
    },
    {
      path: '/login',
      name: 'Login',
      component: () => import('@/views/Login.vue'),
      meta: { requiresGuest: true, noindex: true }
    },
    {
      path: '/register',
      name: 'Register',
      component: () => import('@/views/Register.vue'),
      meta: { requiresGuest: true, noindex: true }
    },
    {
      path: '/daftar-ppdb',
      name: 'PpdbPublicRegister',
      component: () => import('@/views/PpdbPublicRegister.vue'),
      meta: { index: true }
    },
    {
      path: '/:npsn/daftar-ppdb',
      name: 'PpdbPublicRegisterByNpsn',
      component: () => import('@/views/PpdbPublicRegister.vue'),
      meta: { index: true }
    },
    {
      path: '/:npsn/ebooks',
      name: 'PublicEbooks',
      component: () => import('@/views/PublicEbooks.vue'),
      meta: { index: true }
    },
    {
      path: '/:npsn/buku-tamu',
      name: 'PublicGuestBook',
      component: () => import('@/views/PublicGuestBook.vue'),
      meta: { index: true }
    },
    {
      path: '/cek-hasil-ppdb',
      name: 'PpdbCheckResult',
      component: () => import('@/views/PpdbCheckResult.vue'),
      meta: { index: true }
    },
    {
      path: '/lengkapi-berkas-ppdb',
      name: 'PpdbLengkapiBerkas',
      component: () => import('@/views/PpdbLengkapiBerkas.vue'),
      meta: { index: true }
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
      path: '/teacher/wali',
      name: 'TeacherWali',
      component: () => import('@/views/TeacherWali.vue'),
      meta: { requiresAuth: true, requiresTeacher: true }
    },
    {
      path: '/teacher/mapel',
      name: 'TeacherMapel',
      component: () => import('@/views/TeacherMapel.vue'),
      meta: { requiresAuth: true, requiresTeacher: true }
    },
    {
      path: '/teacher/today',
      name: 'TeacherToday',
      component: () => import('@/views/TeacherToday.vue'),
      meta: { requiresAuth: true, requiresTeacher: true, requiresTeachingAssignments: true }
    },
    {
      path: '/teacher/jadwal',
      name: 'TeacherSchedule',
      component: () => import('@/views/TeacherSchedule.vue'),
      meta: { requiresAuth: true, requiresTeacher: true, requiresTeachingAssignments: true }
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
      path: '/super-admin/monetisasi',
      name: 'SuperAdminMonetization',
      component: () => import('@/views/SuperAdminMonetization.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
    {
      path: '/billing',
      name: 'BillingOverview',
      component: () => import('@/views/BillingOverview.vue'),
      meta: { requiresAuth: true, requiresMonetization: true, requiresInstitutionAdmin: true }
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
      path: '/student/absensi',
      name: 'StudentAttendance',
      component: () => import('@/views/StudentAttendance.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/ebooks',
      name: 'StudentEbooks',
      component: () => import('@/views/StudentEbooks.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/uks',
      name: 'StudentUks',
      component: () => import('@/views/StudentUks.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/keuangan',
      name: 'StudentFinance',
      component: () => import('@/views/StudentFinance.vue'),
      meta: { requiresAuth: true, requiresStudent: true }
    },
    {
      path: '/student/pkl',
      name: 'StudentPkl',
      component: () => import('@/views/StudentPkl.vue'),
      meta: { requiresAuth: true, requiresStudent: true, requiresVocational: true }
    },
    {
      path: '/student/bkk',
      name: 'StudentBkk',
      component: () => import('@/views/StudentBkk.vue'),
      meta: { requiresAuth: true, requiresStudent: true, requiresVocational: true }
    },
    {
      path: '/parent/dashboard',
      name: 'ParentDashboard',
      component: () => import('@/views/ParentDashboard.vue'),
      meta: { requiresAuth: true, requiresParent: true }
    },
    {
      path: '/parent/pengumuman',
      name: 'ParentAnnouncements',
      component: () => import('@/views/ParentAnnouncements.vue'),
      meta: { requiresAuth: true, requiresParent: true }
    },
    {
      path: '/parent/anak/:studentId/jadwal',
      name: 'ParentChildSchedule',
      component: () => import('@/views/ParentChildDetail.vue'),
      props: { section: 'jadwal' },
      meta: { requiresAuth: true, requiresParent: true }
    },
    {
      path: '/parent/anak/:studentId/nilai',
      name: 'ParentChildGrades',
      component: () => import('@/views/ParentChildDetail.vue'),
      props: { section: 'nilai' },
      meta: { requiresAuth: true, requiresParent: true }
    },
    {
      path: '/parent/anak/:studentId/absensi',
      name: 'ParentChildAttendance',
      component: () => import('@/views/ParentChildDetail.vue'),
      props: { section: 'absensi' },
      meta: { requiresAuth: true, requiresParent: true }
    },
    {
      path: '/parent/anak/:studentId/pelanggaran',
      name: 'ParentChildViolations',
      component: () => import('@/views/ParentChildDetail.vue'),
      props: { section: 'pelanggaran' },
      meta: { requiresAuth: true, requiresParent: true }
    },
    {
      path: '/school-content',
      name: 'SchoolContent',
      component: () => import('@/views/SchoolContent.vue'),
      meta: { requiresAuth: true, requiresModule: 'school_content' }
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
      path: '/siswa-keluar',
      name: 'SiswaKeluar',
      component: () => import('@/views/SiswaKeluar.vue'),
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
      redirect: '/bk/pelanggaran',
    },
    {
      path: '/bk/pelanggaran',
      name: 'BkPelanggaran',
      component: () => import('../views/Violation.vue'),
      meta: { requiresAuth: true, requiresModule: 'violation', bkMode: 'pelanggaran' },
    },
    {
      path: '/bk/prestasi',
      name: 'BkPrestasi',
      component: () => import('../views/Violation.vue'),
      meta: { requiresAuth: true, requiresModule: 'violation', bkMode: 'prestasi' },
    },
    {
      path: '/counseling',
      name: 'Counseling',
      component: () => import('../views/Counseling.vue'),
      meta: { requiresAuth: true, requiresModule: 'counseling' }
    },
    {
      path: '/laporan-bk',
      redirect: '/bk/laporan',
    },
    {
      path: '/bk/laporan',
      name: 'LaporanBk',
      component: () => import('../views/LaporanBk.vue'),
      meta: { requiresAuth: true, requiresAnyModule: ['violation', 'counseling', 'bk_report'], reportScope: 'combined' },
    },
    {
      path: '/bk/laporan/pelanggaran',
      name: 'LaporanPelanggaran',
      component: () => import('../views/LaporanBk.vue'),
      meta: { requiresAuth: true, requiresAnyModule: ['violation', 'counseling', 'bk_report'], reportScope: 'violations' },
    },
    {
      path: '/bk/laporan/prestasi',
      name: 'LaporanPrestasi',
      component: () => import('../views/LaporanBk.vue'),
      meta: { requiresAuth: true, requiresAnyModule: ['violation', 'counseling', 'bk_report'], reportScope: 'achievements', reportPurpose: 'akreditasi' },
    },
    {
      path: '/bk/laporan/apresiasi',
      name: 'LaporanApresiasi',
      component: () => import('../views/LaporanBk.vue'),
      meta: { requiresAuth: true, requiresAnyModule: ['violation', 'counseling', 'bk_report'], reportScope: 'achievements', reportPurpose: 'apresiasi' },
    },
    {
      path: '/uks',
      name: 'Uks',
      component: () => import('../views/Uks.vue'),
      meta: { requiresAuth: true, requiresModule: 'uks' }
    },
    {
      path: '/uks/stok',
      name: 'UksStock',
      component: () => import('../views/UksStock.vue'),
      meta: { requiresAuth: true, requiresModule: 'uks' }
    },
    {
      path: '/laporan-uks',
      name: 'LaporanUks',
      component: () => import('../views/LaporanUks.vue'),
      meta: { requiresAuth: true, requiresModule: 'uks' }
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
      path: '/industry-partners',
      name: 'IndustryPartners',
      component: () => import('@/views/Pkl/IndustryPartners.vue'),
      meta: { requiresAuth: true, requiresAnyModule: ['pkl', 'bkk'], requiresVocational: true }
    },
    {
      path: '/pkl',
      name: 'Pkl',
      component: () => import('@/views/Pkl/Index.vue'),
      meta: { requiresAuth: true, requiresModule: 'pkl', requiresVocational: true }
    },
    {
      path: '/bkk',
      name: 'Bkk',
      component: () => import('@/views/Bkk/Index.vue'),
      meta: { requiresAuth: true, requiresModule: 'bkk', requiresVocational: true }
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
      meta: { requiresAuth: true, requiresModule: 'schedule', blocksTeacher: true }
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
      meta: { requiresAuth: true, requiresAnyModule: ['attendance', 'teaching_journal'] }
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
      path: '/raport-kelas',
      name: 'RaportKelas',
      component: () => import('@/views/RaportKelas.vue'),
      meta: { requiresAuth: true, requiresModule: 'grade_book' }
    },
    {
      path: '/teacher',
      name: 'Teacher',
      component: () => import('@/views/Teacher.vue'),
      meta: { requiresAuth: true, requiresModule: 'teacher' }
    },
    {
      path: '/kepegawaian',
      name: 'Kepegawaian',
      component: () => import('@/views/Kepegawaian.vue'),
      meta: { requiresAuth: true, requiresModule: 'kepegawaian' }
    },
    {
      path: '/teacher/cuti',
      name: 'TeacherLeave',
      component: () => import('@/views/TeacherLeave.vue'),
      meta: { requiresAuth: true, requiresTeacher: true }
    },
    {
      path: '/teacher-mutation',
      name: 'TeacherMutation',
      component: () => import('@/views/TeacherMutation.vue'),
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
      path: '/program-keahlian',
      name: 'ProgramKeahlian',
      component: () => import('@/views/ProgramKeahlian.vue'),
      meta: { requiresAuth: true, requiresModule: 'class', requiresVocational: true }
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
      path: '/feedback',
      name: 'FeedbackTickets',
      component: () => import('@/views/FeedbackTickets.vue'),
      meta: { requiresAuth: true, requiresFeedbackAccess: true }
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
      redirect: { name: 'InventoryBeranda' }
    },
    ...inventoryRoutes,
    {
      path: '/inventory/scan',
      name: 'InventoryQrScan',
      component: () => import('@/views/InventoryQrScan.vue'),
      meta: {
        requiresAuth: true,
        requiresModule: 'inventory',
        allowLabResponsible: true,
        allowRoomResponsible: true,
        inventoryLabAllowed: true,
        inventoryRoomAllowed: true,
      }
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
      path: '/academic-calendar',
      name: 'AcademicCalendar',
      component: () => import('@/views/AcademicCalendar.vue'),
      meta: { requiresAuth: true, requiresModule: 'academic_calendar' }
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
      meta: { requiresAuth: true, allowMustChangePassword: true }
    },
    {
      path: '/ganti-sandi-wajib',
      name: 'ForceChangePassword',
      component: () => import('@/views/ForceChangePassword.vue'),
      meta: { requiresAuth: true, allowMustChangePassword: true, hideLayout: true }
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
      redirect: { name: 'PpdbRingkasan' }
    },
    {
      path: '/ppdb/ringkasan',
      name: 'PpdbRingkasan',
      component: () => import('@/views/Ppdb/Index.vue'),
      meta: { requiresAuth: true, requiresModule: 'ppdb' }
    },
    {
      path: '/ppdb/konfigurasi',
      name: 'PpdbKonfigurasi',
      component: () => import('@/views/Ppdb/Konfigurasi.vue'),
      meta: { requiresAuth: true, requiresModule: 'ppdb' }
    },
    {
      path: '/ppdb/pendaftar',
      name: 'PpdbPendaftar',
      component: () => import('@/views/Ppdb/Pendaftar.vue'),
      meta: { requiresAuth: true, requiresModule: 'ppdb' }
    },
    {
      path: '/ppdb/pendaftar/:id',
      name: 'PpdbPendaftarDetail',
      component: () => import('@/views/Ppdb/PendaftarDetail.vue'),
      meta: { requiresAuth: true, requiresModule: 'ppdb' }
    },
    {
      path: '/ppdb/statistik',
      name: 'PpdbStatistik',
      component: () => import('@/views/Ppdb/Statistik.vue'),
      meta: { requiresAuth: true, requiresModule: 'ppdb' }
    },
    {
      path: '/ppdb/pembayaran',
      name: 'PpdbPembayaran',
      component: () => import('@/views/Ppdb/Pembayaran.vue'),
      meta: { requiresAuth: true, requiresModule: 'ppdb' }
    },
    {
      path: '/keuangan',
      name: 'Keuangan',
      redirect: { name: 'KeuanganJenisBiaya' }
    },
    {
      path: '/keuangan/jenis-biaya',
      name: 'KeuanganJenisBiaya',
      component: () => import('@/views/Keuangan/JenisBiaya.vue'),
      meta: { requiresAuth: true, requiresModule: 'finance' }
    },
    {
      path: '/keuangan/spp',
      name: 'KeuanganSpp',
      component: () => import('@/views/Keuangan/Spp.vue'),
      meta: { requiresAuth: true, requiresModule: 'finance' }
    },
    {
      path: '/keuangan/tagihan',
      name: 'KeuanganTagihan',
      component: () => import('@/views/Keuangan/Tagihan.vue'),
      meta: { requiresAuth: true, requiresModule: 'finance' }
    },
    {
      path: '/keuangan/pembayaran',
      name: 'KeuanganPembayaran',
      component: () => import('@/views/Keuangan/Pembayaran.vue'),
      meta: { requiresAuth: true, requiresModule: 'finance' }
    },
    {
      path: '/keuangan/pengeluaran',
      name: 'KeuanganPengeluaran',
      component: () => import('@/views/Keuangan/Pengeluaran.vue'),
      meta: { requiresAuth: true, requiresAnyModule: ['finance', 'payroll'] }
    },
    {
      path: '/keuangan/tunggakan',
      name: 'KeuanganTunggakan',
      component: () => import('@/views/Keuangan/Tunggakan.vue'),
      meta: { requiresAuth: true, requiresModule: 'finance' }
    },
    {
      path: '/keuangan/laporan',
      name: 'KeuanganLaporan',
      component: () => import('@/views/Keuangan/Laporan.vue'),
      meta: { requiresAuth: true, requiresModule: 'finance' }
    },
    {
      path: '/penggajian',
      name: 'Penggajian',
      redirect: { name: 'PenggajianProses' }
    },
    {
      path: '/penggajian/komponen',
      name: 'PenggajianKomponen',
      component: () => import('@/views/Penggajian/Komponen.vue'),
      meta: { requiresAuth: true, requiresModule: 'payroll' }
    },
    {
      path: '/penggajian/tunjangan-jabatan',
      name: 'PenggajianTunjanganJabatan',
      component: () => import('@/views/Penggajian/TunjanganJabatan.vue'),
      meta: { requiresAuth: true, requiresModule: 'payroll' }
    },
    {
      path: '/penggajian/profil',
      name: 'PenggajianProfil',
      component: () => import('@/views/Penggajian/Profil.vue'),
      meta: { requiresAuth: true, requiresModule: 'payroll' }
    },
    {
      path: '/penggajian/periode',
      name: 'PenggajianPeriode',
      component: () => import('@/views/Penggajian/Periode.vue'),
      meta: { requiresAuth: true, requiresModule: 'payroll' }
    },
    {
      path: '/penggajian/proses',
      name: 'PenggajianProses',
      component: () => import('@/views/Penggajian/Proses.vue'),
      meta: { requiresAuth: true, requiresModule: 'payroll' }
    },
    {
      path: '/teacher/slip-gaji',
      name: 'TeacherSlipGaji',
      component: () => import('@/views/Penggajian/SlipGaji.vue'),
      meta: { requiresAuth: true, requiresTeacher: true }
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
      component: () => import('@/views/SchoolPublic.vue'),
      meta: { index: true }
    }
  ],
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) {
      return {
        el: to.hash,
        top: 80,
        behavior: 'smooth',
      }
    }
    // Selalu mulai dari atas saat pindah halaman (mis. Panduan → Catatan Rilis)
    return { top: 0, left: 0 }
  },
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
  if (role === 'parent') {
    return '/parent/dashboard'
  }
  return '/dashboard'
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

  // Force password change gate (e.g. student default birth-date password)
  if (authStore.isAuthenticated) {
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch {
        // ignore
      }
    }
    if (authStore.user?.must_change_password && !to.meta.allowMustChangePassword) {
      next({ name: 'ForceChangePassword' })
      return
    }
  }

  if (to.meta.blocksTeacher) {
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch {
        authStore.isAuthenticated = false
        authStore.user = null
        next('/login')
        return
      }
    }
    if (authStore.user?.role === 'teacher' || authStore.user?.role === 'staff') {
      next('/teacher/jadwal')
      return
    }
  }

  if (authStore.isAuthenticated && to.meta.requiresVocational) {
    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch {
        // ignore
      }
    }
    if (!isVocationalLevel(getActiveInstitutionLevel(authStore))) {
      const defaultRoute = getDefaultRoute(authStore.user?.role)
      if (to.path !== defaultRoute) {
        next(defaultRoute)
      } else {
        next()
      }
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
  } else if (to.meta.requiresFeedbackAccess) {
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
    const allowed = ['super_admin', 'institution_admin', 'admin', 'teacher', 'staff'].includes(role)
    if (!allowed) {
      next(getDefaultRoute(role))
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
    } else if (
      to.meta.requiresTeachingAssignments
      && !(authStore.user?.teaching_assignments || []).length
    ) {
      next('/teacher/dashboard')
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
  } else if (to.meta.requiresParent) {
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

    if (authStore.user?.role !== 'parent') {
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
  } else if (to.meta.requiresMonetization || to.meta.requiresInstitutionAdmin) {
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
    if (to.meta.requiresInstitutionAdmin && !['institution_admin', 'admin'].includes(role)) {
      next(getDefaultRoute(role))
      return
    }

    if (to.meta.requiresMonetization && !authStore.isMonetizationVisible) {
      next(getDefaultRoute(role))
      return
    }

    next()
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
      const tab = INVENTORY_TAB_BY_ROUTE_NAME[to.name]
      const roomScopedAllowed =
        (to.meta.allowRoomResponsible || to.meta.allowLabResponsible)
        && (authStore.user?.is_room_responsible || authStore.user?.is_lab_responsible)
        && (to.meta.inventoryRoomAllowed !== false && to.meta.inventoryLabAllowed !== false)
        && (!tab || canAccessInventoryTab(authStore.user, tab))

      if (roomScopedAllowed) {
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

router.afterEach((to) => {
  applyRouteSeo(to)
})

export default router
