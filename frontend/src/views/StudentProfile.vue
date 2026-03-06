<template>
  <Layout>
    <div class="page">
      <div class="page-header">
        <h1>Profil Saya</h1>
        <p class="page-subtitle">Data diri Anda (hanya tampilan)</p>
      </div>

      <div v-if="!profile" class="alert alert-warning">
        Data profil siswa (NIS, NISN, Kelas) belum dilengkapi atau akun belum dihubungkan dengan data siswa. Hubungi operator sekolah.
      </div>

      <div class="content-wrap">
        <dl class="profile-list">
          <div class="profile-row">
            <dt>Nama</dt>
            <dd>{{ user?.name || '-' }}</dd>
          </div>
          <div class="profile-row">
            <dt>Email</dt>
            <dd>{{ user?.email || '-' }}</dd>
          </div>
          <div v-if="profile" class="profile-row">
            <dt>NIS</dt>
            <dd>{{ profile.nis || '-' }}</dd>
          </div>
          <div v-if="profile" class="profile-row">
            <dt>NISN</dt>
            <dd>{{ profile.nisn || '-' }}</dd>
          </div>
          <div v-if="profile" class="profile-row">
            <dt>Kelas</dt>
            <dd>{{ profile.class_name || profile.class || '-' }}</dd>
          </div>
          <div v-if="profile" class="profile-row">
            <dt>Status</dt>
            <dd>{{ profile.status || '-' }}</dd>
          </div>
        </dl>
        <p class="profile-note">Untuk mengubah data profil, hubungi operator sekolah.</p>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { computed } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const user = computed(() => authStore.user)
const profile = computed(() => authStore.user?.student_profile)
</script>

<style scoped>
.page { max-width: 100%; padding: 0; }
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; }
.page-subtitle { font-size: 14px; color: #64748b; margin: 0; }

.content-wrap { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; max-width: 480px; }

.profile-list { margin: 0; }
.profile-row { display: flex; flex-wrap: wrap; padding: 12px 0; border-bottom: 1px solid #e2e8f0; gap: 12px; }
.profile-row:last-of-type { border-bottom: none; }
.profile-row dt { font-size: 13px; font-weight: 600; color: #64748b; min-width: 100px; margin: 0; }
.profile-row dd { font-size: 14px; color: #0f172a; margin: 0; flex: 1; }

.profile-note { font-size: 13px; color: #94a3b8; margin: 16px 0 0 0; }

.alert {
  padding: 14px 18px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 14px;
  line-height: 1.5;
  background: #fef3c7;
  border: 1px solid #f59e0b;
  color: #92400e;
}

.back-link { display: inline-block; margin-top: 20px; color: #059669; text-decoration: none; font-weight: 600; font-size: 14px; }
.back-link:hover { color: #047857; text-decoration: underline; }
</style>
