<template>
  <nav class="inventory-subnav" role="navigation" aria-label="Menu inventaris lainnya">
    <span class="inventory-subnav-label">Lainnya</span>
    <router-link
      v-for="item in INVENTORY_SUBNAV_ITEMS"
      :key="item.path"
      :to="item.path"
      class="sub-nav-btn"
      active-class="active"
    >
      {{ item.label }}
    </router-link>
  </nav>
</template>

<script setup>
import { INVENTORY_SUBNAV_ITEMS } from '@/composables/inventory/inventoryRoutes'
</script>
