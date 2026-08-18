<template>
  <Layout>
    <div class="billing-page">
      <div class="page-header">
        <div>
          <h2>Paket &amp; Add-on</h2>
          <p>Ringkasan langganan dan peningkatan untuk institusi Anda</p>
        </div>
      </div>

      <div v-if="loading" class="muted">Memuat...</div>
      <div v-else-if="error" class="form-error">{{ error }}</div>
      <template v-else>
        <section class="panel">
          <h3>Penyimpanan</h3>
          <p class="muted">
            Kuota {{ features?.storage?.quota_mb ?? '—' }} MB
            + add-on {{ features?.storage?.addon_mb ?? 0 }} MB
            = {{ features?.storage?.total_mb ?? '—' }} MB
          </p>
        </section>

        <section class="panel panel-spaced">
          <h3>Paket tersedia</h3>
          <ul class="list">
            <li v-for="plan in plans" :key="plan.id">
              <strong>{{ plan.name }}</strong>
              <span class="muted"> — {{ plan.storage_quota_mb }} MB
                <template v-if="plan.includes_online_exam"> · termasuk Ujian Online</template>
              </span>
            </li>
          </ul>
        </section>

        <section class="panel panel-spaced">
          <h3>Add-on tersedia</h3>
          <ul class="list">
            <li v-for="addon in addons" :key="addon.id">
              <strong>{{ addon.name }}</strong>
              <span class="muted"> — {{ addon.description || addon.key }}</span>
            </li>
          </ul>
        </section>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Layout from '@/components/Layout.vue'
import { billingApi } from '@/api/billing'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const loading = ref(true)
const error = ref('')
const features = ref(null)
const plans = ref([])
const addons = ref([])

onMounted(async () => {
  if (!authStore.isMonetizationVisible) {
    router.replace('/dashboard')
    return
  }

  loading.value = true
  try {
    const res = await billingApi.getOverview()
    features.value = res.data?.data?.features || null
    plans.value = res.data?.data?.plans || []
    addons.value = res.data?.data?.addons || []
  } catch (err) {
    if (err.response?.status === 404) {
      router.replace('/dashboard')
      return
    }
    error.value = err.response?.data?.message || 'Gagal memuat data billing'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.billing-page {
  max-width: 720px;
}

.page-header {
  margin-bottom: 18px;
}

.page-header h2 {
  margin: 0 0 4px;
}

.page-header p,
.muted {
  margin: 0;
  color: #64748b;
  font-size: 14px;
}

.panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px;
}

.panel-spaced {
  margin-top: 12px;
}

.panel h3 {
  margin: 0 0 8px;
  font-size: 16px;
}

.list {
  margin: 0;
  padding-left: 18px;
}

.list li {
  margin-bottom: 6px;
}

.form-error {
  color: #b91c1c;
}
</style>
