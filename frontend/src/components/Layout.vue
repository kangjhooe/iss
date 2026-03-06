<template>
  <div class="layout">
    <!-- Mobile Menu Button -->
    <button 
      @click="toggleSidebar" 
      class="mobile-menu-btn"
      :aria-label="sidebarOpen ? 'Tutup menu' : 'Buka menu'"
    >
      <svg v-if="!sidebarOpen" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M3 12H21M3 6H21M3 18H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>

    <!-- Overlay for mobile -->
    <div 
      v-if="sidebarOpen" 
      class="sidebar-overlay"
      @click="closeSidebar"
    ></div>

    <nav class="sidebar" :class="{ 'sidebar-open': sidebarOpen }">
      <div class="logo">
        <div class="logo-icon">
          <AppLogo :size="32" />
        </div>
        <div class="logo-text">
          <h2>{{ appName }}</h2>
        </div>
      </div>
      
      <ul class="nav-menu">
        <template v-for="entry in menuEntries" :key="entry.key">
          <li v-if="entry.type === 'link'">
            <router-link :to="entry.to" class="nav-item">
              <component :is="entry.icon" />
              <span>{{ entry.label }}</span>
            </router-link>
          </li>
          <li v-else-if="entry.type === 'group'" class="nav-group">
            <button
              type="button"
              class="nav-group-head"
              :class="{ 'nav-group-head--active': hasActiveChild(entry) }"
              :aria-expanded="isGroupExpanded(entry.key)"
              @click="toggleGroup(entry.key)"
            >
              <component :is="entry.icon" />
              <span>{{ entry.label }}</span>
              <svg class="nav-group-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" :class="{ 'nav-group-chevron--open': isGroupExpanded(entry.key) }">
                <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <Transition name="nav-group">
              <div v-show="isGroupExpanded(entry.key)" class="nav-group-body">
                <router-link
                  v-for="child in entry.visibleChildren"
                  :key="child.to"
                  :to="child.to"
                  class="nav-subitem"
                  :class="{ 'nav-subitem--active': child.active }"
                >
                  {{ child.label }}
                </router-link>
              </div>
            </Transition>
          </li>
        </template>
      </ul>
      
      <div class="user-section" ref="userMenuRef">
        <button
          type="button"
          class="user-menu-trigger"
          @click.stop="userMenuOpen = !userMenuOpen"
          :aria-expanded="userMenuOpen"
          aria-haspopup="true"
          aria-label="Menu akun"
        >
          <div class="user-avatar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="user-details">
            <p class="user-name">{{ authStore.user?.name || 'User' }}</p>
            <p class="user-email">{{ authStore.user?.email || 'email@example.com' }}</p>
          </div>
          <svg class="user-menu-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" :class="{ 'user-menu-chevron--open': userMenuOpen }">
            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <Transition name="user-menu">
          <div v-show="userMenuOpen" class="user-menu-dropdown" role="menu">
            <router-link
              to="/pengaturan-akun"
              class="user-menu-item"
              :class="{ 'user-menu-item--active': $route.path === '/pengaturan-akun' }"
              role="menuitem"
              @click="userMenuOpen = false"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 3v2M12 19v2M3 12h2M19 12h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <span>Pengaturan akun</span>
            </router-link>
            <button type="button" class="user-menu-item user-menu-item--logout" role="menuitem" @click="handleLogout">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 17L21 12L16 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Keluar</span>
            </button>
          </div>
        </Transition>
      </div>
    </nav>
    
    <main class="main-content">
      <header class="topbar">
        <div class="topbar-content">
          <h1>{{ pageTitle }}</h1>
          <div class="topbar-actions">
            <router-link
              v-if="showNotificationBell"
              to="/notifications"
              class="notification-bell"
              title="Notifikasi"
            >
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span v-if="unreadNotificationCount > 0" class="notification-badge">{{ unreadNotificationCount > 99 ? '99+' : unreadNotificationCount }}</span>
            </router-link>
            <div class="breadcrumb">
              <span>Home</span>
              <span class="separator">/</span>
              <span class="current">{{ pageTitle }}</span>
            </div>
          </div>
        </div>
      </header>
      <div class="content">
        <ErrorBoundary>
          <slot />
        </ErrorBoundary>
      </div>
    </main>

    <!-- Bottom Navigation (mobile - admin sekolah & guru) -->
    <nav v-if="showBottomNav" class="bottom-nav" aria-label="Menu utama">
      <router-link
        v-for="item in bottomNavItems"
        :key="item.to"
        :to="item.to"
        class="bottom-nav-item"
        :class="{ 'bottom-nav-item-active': isBottomNavActive(item.to) }"
      >
        <span class="bottom-nav-icon">
          <svg v-if="item.icon === 'home'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'student'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'mutation'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M17 8L21 12L17 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'violation'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'counseling'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'extracurricular'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'report'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 3V8H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'correspondence'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'institution'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 7H15M9 12H15M9 17H13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'academic-year'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'request'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 3V8H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'class'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'archive'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M21 8V21H3V8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M23 3H1V8H23V3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M10 12H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'attendance'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5C15 6.10457 14.1046 7 13 7H11C9.89543 7 9 6.10457 9 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'guest'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'library'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else-if="item.icon === 'settings'" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 3v2M12 19v2M3 12h2M19 12h2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        <span class="bottom-nav-label">{{ item.label }}</span>
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { appName } from '@/config/app'
import ErrorBoundary from '@/components/ErrorBoundary.vue'
import AppLogo from '@/components/AppLogo.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const MOBILE_BREAKPOINT = 768
const sidebarOpen = ref(false)
const userMenuOpen = ref(false)
const userMenuRef = ref(null)
const expandedGroups = ref(new Set())

// Top-level menu icons (only these use icons per spec)
const IconDashboard = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M9 22V12H15V22', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconDatabase = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M4 7v10c0 2.21 3.58 4 8 4s8-1.79 8-4V7', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M4 7c0 2.21 3.58 4 8 4s8-1.79 8-4-3.58-4-8-4-8 1.79-8 4z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M4 7c0-2.21 3.58-4 8-4s8 1.79 8 4', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconAcademic = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconStudents = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M12 3L2 9l10 6 10-6L12 3z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M2 9v10l10 5 10-5V9', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconAdmin = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M22 6L12 13L2 6', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconSettings = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M19.4 15C19.2669 15.3016 19.2272 15.6362 19.286 15.9606C19.3448 16.285 19.4995 16.5843 19.73 16.82L19.79 16.88C19.976 17.0657 20.1235 17.2863 20.2241 17.5291C20.3248 17.7719 20.3766 18.0322 20.3766 18.295C20.3766 18.5578 20.3248 18.8181 20.2241 19.0609C20.1235 19.3037 19.976 19.5243 19.79 19.71C19.6043 19.896 19.3837 20.0435 19.1409 20.1441C18.8981 20.2448 18.6378 20.2966 18.375 20.2966C18.1122 20.2966 17.8519 20.2448 17.6091 20.1441C17.3663 20.0435 17.1457 19.896 16.96 19.71L16.9 19.65C16.6643 19.4195 16.365 19.2648 16.0406 19.206C15.7162 19.1472 15.3816 19.1869 15.08 19.32C14.7842 19.4468 14.532 19.6572 14.3543 19.9255C14.1766 20.1938 14.0813 20.5082 14.08 20.83V21C14.08 21.5304 13.8693 22.0391 13.4942 22.4142C13.1191 22.7893 12.6104 23 12.08 23C11.5496 23 11.0409 22.7893 10.6658 22.4142C10.2907 22.0391 10.08 21.5304 10.08 21V20.91C10.0723 20.5795 9.96512 20.258 9.77251 19.9887C9.5799 19.7194 9.31074 19.5143 9 19.4C8.69838 19.2669 8.36381 19.2272 8.03941 19.286C7.71502 19.3448 7.41568 19.4995 7.18 19.73L7.12 19.79C6.93425 19.976 6.71368 20.1235 6.47088 20.2241C6.22808 20.3248 5.96783 20.3766 5.705 20.3766C5.44217 20.3766 5.18192 20.3248 4.93912 20.2241C4.69632 20.1235 4.47575 19.976 4.29 19.79C4.10405 19.6043 3.95653 19.3837 3.85588 19.1409C3.75523 18.8981 3.70343 18.6378 3.70343 18.375C3.70343 18.1122 3.75523 17.8519 3.85588 17.6091C3.95653 17.3663 4.10405 17.1457 4.29 16.96L4.35 16.9C4.58054 16.6643 4.73519 16.365 4.794 16.0406C4.85282 15.7162 4.81312 15.3816 4.68 15.08C4.55324 14.7842 4.34276 14.532 4.07447 14.3543C3.80618 14.1766 3.49179 14.0813 3.17 14.08H3C2.46957 14.08 1.96086 13.8693 1.58579 13.4942C1.21071 13.1191 1 12.6104 1 12.08C1 11.5496 1.21071 11.0409 1.58579 10.6658C1.96086 10.2907 2.46957 10.08 3 10.08H3.09C3.42054 10.0723 3.742 9.96512 4.0113 9.77251C4.28059 9.5799 4.48572 9.31074 4.6 9C4.73312 8.69838 4.77282 8.36381 4.714 8.03941C4.65519 7.71502 4.50054 7.41568 4.27 7.18L4.21 7.12C4.02405 6.93425 3.87653 6.71368 3.77588 6.47088C3.67523 6.22808 3.62343 5.96783 3.62343 5.705C3.62343 5.44217 3.67523 5.18192 3.77588 4.93912C3.87653 4.69632 4.02405 4.47575 4.21 4.29C4.39575 4.10405 4.61632 3.95653 4.85912 3.85588C5.10192 3.75523 5.36217 3.70343 5.625 3.70343C5.88783 3.70343 6.14808 3.75523 6.39088 3.85588C6.63368 3.95653 6.85425 4.10405 7.04 4.29L7.1 4.35C7.33568 4.58054 7.63502 4.73519 7.95941 4.794C8.28381 4.85282 8.61838 4.81312 8.92 4.68H9C9.29577 4.55324 9.54802 4.34276 9.72569 4.07447C9.90337 3.80618 9.99872 3.49179 10 3.17V3C10 2.46957 10.2107 1.96086 10.5858 1.58579C10.9609 1.21071 11.4696 1 12 1C12.5304 1 13.0391 1.21071 13.4142 1.58579C13.7893 1.96086 14 2.46957 14 3V3.09C14.0013 3.41179 14.0966 3.72618 14.2743 3.99447C14.452 4.26276 14.7042 4.47324 15 4.6C15.3016 4.73312 15.6362 4.77282 15.9606 4.714C16.285 4.65519 16.5843 4.50054 16.82 4.27L16.88 4.21C17.0657 4.02405 17.2863 3.87653 17.5291 3.77588C17.7719 3.67523 18.0322 3.62343 18.295 3.62343C18.5578 3.62343 18.8181 3.67523 19.0609 3.77588C19.3037 3.87653 19.5243 4.02405 19.71 4.21C19.896 4.39575 20.0435 4.61632 20.1441 4.85912C20.2448 5.10192 20.2966 5.36217 20.2966 5.625C20.2966 5.88783 20.2448 6.14808 20.1441 6.39088C20.0435 6.63368 19.896 6.85425 19.71 7.04L19.65 7.1C19.4195 7.33568 19.2648 7.63502 19.206 7.95941C19.1472 8.28381 19.1869 8.61838 19.32 8.92V9C19.4468 9.29577 19.6572 9.54802 19.9255 9.72569C20.1938 9.90337 20.5082 9.99872 20.83 10H21C21.5304 10 22.0391 10.2107 22.4142 10.5858C22.7893 10.9609 23 11.4696 23 12C23 12.5304 22.7893 13.0391 22.4142 13.4142C22.0391 13.7893 21.5304 14 21 14H20.91C20.5882 14.0013 20.2738 14.0966 20.0055 14.2743C19.7372 14.452 19.5268 14.7042 19.4 15Z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconAttendance = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5C15 6.10457 14.1046 7 13 7H11C9.89543 7 9 6.10457 9 5Z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M9 12L11 14L15 10', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconGrade = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M9 19V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M3 19V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M15 19V15a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconCounseling = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconPoints = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconModuleAccess = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconAuditLog = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M14 2v6h6', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M16 13H8', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M16 17H8', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M10 9H8', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconExam = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5C15 6.10457 14.1046 7 13 7H11C9.89543 7 9 6.10457 9 5Z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M9 12h6M9 16h6', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])

const canAccessModule = (moduleKey) => {
  const role = authStore.user?.role
  if (!role) return false
  if (role === 'super_admin' || role === 'admin' || role === 'institution_admin') {
    return true
  }
  return (authStore.user?.permissions || []).includes(moduleKey)
}

function getDashboardTo() {
  const role = authStore.user?.role
  if (role === 'super_admin') return '/super-admin/dashboard'
  if (role === 'teacher' || role === 'staff') return '/teacher/dashboard'
  if (role === 'student') return '/student/dashboard'
  return '/dashboard'
}

const menuEntries = computed(() => {
  const role = authStore.user?.role
  const path = route.path
  const addVisible = (group) => {
    const visible = (group.children || []).filter(c => c.visible).map(c => {
      const toPath = c.to?.split('?')[0] ?? c.to
      let active = !!toPath && (path === toPath || path.startsWith(toPath + '/'))
      if (active && c.to?.includes('?')) {
        const qs = c.to.split('?')[1] || ''
        const params = new URLSearchParams(qs)
        for (const [k, v] of params) {
          if (route.query[k] !== v) {
            active = false
            break
          }
        }
      }
      return { ...c, active }
    })
    return { ...group, visibleChildren: visible }
  }
  if (role === 'super_admin') {
    return [
      { type: 'link', key: 'dashboard', to: '/super-admin/dashboard', label: 'Dashboard', icon: IconDashboard },
      addVisible({ type: 'group', key: 'sistem', label: 'Sistem', icon: IconSettings, children: [
        { to: '/institution', label: 'Kelola Institusi', visible: true },
        { to: '/academic-year', label: 'Tahun Ajaran', visible: true },
        { to: '/super-admin/app-branding', label: 'Branding Aplikasi', visible: true },
        { to: '/institution-change-requests', label: 'Request Perubahan', visible: true }
      ]})
    ]
  }
  if (role === 'student') {
    return [
      { type: 'link', key: 'dashboard', to: '/student/dashboard', label: 'Dashboard', icon: IconDashboard },
      { type: 'link', key: 'student-schedule', to: '/student/jadwal', label: 'Jadwal Saya', icon: IconAcademic },
      { type: 'link', key: 'student-attendance', to: '/student/absensi', label: 'Absensi Saya', icon: IconAttendance },
      { type: 'link', key: 'student-grades', to: '/student/nilai', label: 'Nilai Saya', icon: IconGrade },
      { type: 'link', key: 'student-exam', to: '/ujian-ikuti', label: 'Ikuti Ujian', icon: IconAcademic },
      { type: 'link', key: 'student-violations', to: '/student/pelanggaran-prestasi', label: 'Pelanggaran & Prestasi', icon: IconStudents },
      { type: 'link', key: 'student-counseling', to: '/student/konseling', label: 'Konseling', icon: IconCounseling },
      { type: 'link', key: 'student-extracurricular', to: '/student/ekstrakurikuler', label: 'Ekstrakurikuler', icon: IconStudents },
      { type: 'link', key: 'student-points', to: '/student/poin', label: 'Poin Saya', icon: IconPoints },
      { type: 'link', key: 'student-change-requests', to: '/student/permintaan-perubahan', label: 'Permintaan Perubahan', icon: IconAdmin },
      { type: 'link', key: 'student-profile', to: '/student/profil', label: 'Profil Saya', icon: IconSettings }
    ]
  }
  const entries = [
    { type: 'link', key: 'dashboard', to: getDashboardTo(), label: 'Dashboard', icon: IconDashboard },
    ...(role === 'teacher' || role === 'staff' ? [{ type: 'link', key: 'teacher-profile', to: '/teacher/profile', label: 'Profil Saya', icon: IconSettings }] : []),
    addVisible({ type: 'group', key: 'master', label: 'Master Data', icon: IconDatabase, children: [
      { to: '/institution', label: 'Profil Instansi', visible: canAccessModule('institution') },
      { to: '/facility', label: 'Sarana Prasarana', visible: canAccessModule('facility') },
      { to: '/inventory', label: 'Inventaris', visible: canAccessModule('inventory') },
      { to: '/class', label: 'Kelas', visible: canAccessModule('class') }
    ]}),
    addVisible({ type: 'group', key: 'akademik', label: 'Akademik', icon: IconAcademic, children: [
      { to: '/student', label: 'Data Siswa', visible: canAccessModule('student') },
      { to: '/teacher', label: 'Data Guru', visible: canAccessModule('teacher') },
      { to: '/lesson-schedule', label: 'Jadwal Pelajaran', visible: canAccessModule('schedule') },
      { to: '/teaching-journal', label: 'Jurnal Mengajar', visible: canAccessModule('teaching_journal') },
      { to: '/grade-book', label: 'Buku Nilai', visible: canAccessModule('grade_book') },
      { to: '/raport', label: 'Raport Siswa', visible: canAccessModule('grade_book') }
    ]}),
    addVisible({ type: 'group', key: 'ujian-online', label: 'Ujian Online', icon: IconExam, children: [
      { to: '/ujian-online/exams', label: 'Daftar Ujian', visible: canAccessModule('online_exam') },
      { to: '/ujian-online/sesi?fokus=peserta', label: 'Peserta Ujian', visible: canAccessModule('online_exam') },
      { to: '/ujian-online/sesi?fokus=kontrol', label: 'Kontrol Ujian', visible: canAccessModule('online_exam') },
      { to: '/ujian-online/bank-soal', label: 'Bank Soal', visible: canAccessModule('online_exam') }
    ]}),
    addVisible({ type: 'group', key: 'absensi', label: 'Absensi', icon: IconAttendance, children: [
      { to: '/attendance/student', label: 'Absensi Siswa', visible: canAccessModule('teaching_journal') },
      { to: '/attendance/employee', label: 'Absensi Guru & Staff', visible: canAccessModule('attendance') }
    ]}),
    addVisible({ type: 'group', key: 'kesiswaan', label: 'Kesiswaan', icon: IconStudents, children: [
      { to: '/student-mutation', label: 'Mutasi Siswa', visible: canAccessModule('student') },
      { to: '/naik-kelas', label: 'Naik Kelas', visible: canAccessModule('student') },
      { to: '/alumni', label: 'Alumni', visible: canAccessModule('student') },
      { to: '/student-change-requests', label: 'Permintaan Perubahan Siswa', visible: canAccessModule('student') },
      { to: '/teacher-change-requests', label: 'Permintaan Perubahan Guru', visible: canAccessModule('teacher') },
      { to: '/pengambilan-ijazah', label: 'Pengambilan Ijazah', visible: canAccessModule('document_pickup') },
      { to: '/violation', label: 'Pelanggaran', visible: canAccessModule('violation') },
      { to: '/counseling', label: 'Konseling', visible: canAccessModule('counseling') },
      { to: '/extracurricular', label: 'Ekstrakurikuler', visible: canAccessModule('extracurricular') },
      { to: '/ppdb', label: 'PPDB', visible: canAccessModule('ppdb') }
    ]}),
    addVisible({ type: 'group', key: 'administrasi', label: 'Administrasi', icon: IconAdmin, children: [
      { to: '/correspondence', label: 'Persuratan', visible: canAccessModule('correspondence') },
      { to: '/digital-archive', label: 'Arsip Digital', visible: canAccessModule('digital_archive') },
      { to: '/library', label: 'Perpustakaan', visible: canAccessModule('library') },
      { to: '/lab', label: 'Manajemen Lab', visible: canAccessModule('facility') },
      { to: '/buku-tamu', label: 'Buku Tamu', visible: canAccessModule('guest_book') },
      { to: '/report', label: 'Laporan', visible: canAccessModule('report') }
    ]})
  ]
  if (role === 'institution_admin' || role === 'admin') {
    entries.push({ type: 'link', key: 'pengaturan', to: '/module-access', label: 'Akses Modul', icon: IconModuleAccess })
    entries.push({ type: 'link', key: 'audit-log', to: '/audit-log', label: 'Audit Log', icon: IconAuditLog })
  }
  if (role === 'super_admin') {
    entries.push({ type: 'link', key: 'audit-log', to: '/audit-log', label: 'Audit Log', icon: IconAuditLog })
  }
  return entries.filter(e => e.type === 'link' || (e.type === 'group' && e.visibleChildren?.length > 0))
})
const hasActiveChild = (entry) => {
  if (entry.type !== 'group' || !entry.children) return false
  const path = route.path
  return entry.children.some(c => {
    if (!c.visible) return false
    const toPath = c.to?.split('?')[0] ?? c.to
    return path === c.to || path.startsWith(c.to + '/') || (toPath && (path === toPath || path.startsWith(toPath + '/')))
  })
}
const isGroupExpanded = (key) => expandedGroups.value.has(key)
function toggleGroup(key) {
  const next = new Set(expandedGroups.value)
  if (next.has(key)) next.delete(key)
  else next.add(key)
  expandedGroups.value = next
}
function ensureGroupExpandedForKey(key) {
  if (!expandedGroups.value.has(key)) {
    expandedGroups.value = new Set([...expandedGroups.value, key])
  }
}

watch(() => route.path, (path) => {
  for (const entry of menuEntries.value) {
    if (entry.type === 'group' && entry.children) {
      const hasActive = entry.children.some(c => {
        if (!c.visible) return false
        const toPath = c.to?.split('?')[0] ?? c.to
        return path === c.to || path.startsWith(c.to + '/') || (toPath && (path === toPath || path.startsWith(toPath + '/')))
      })
      if (hasActive) ensureGroupExpandedForKey(entry.key)
    }
  }
}, { immediate: true })

const showBottomNav = computed(() => {
  const role = authStore.user?.role
  return !!role
})

const bottomNavItems = computed(() => {
  const role = authStore.user?.role
  if (role === 'super_admin') {
    return [
      { to: '/super-admin/dashboard', label: 'Beranda', icon: 'home' },
      { to: '/institution', label: 'Institusi', icon: 'institution' },
      { to: '/academic-year', label: 'Tahun Ajaran', icon: 'academic-year' },
      { to: '/institution-change-requests', label: 'Request', icon: 'request' }
    ]
  }
  if (role === 'student') {
    return [
      { to: '/student/dashboard', label: 'Beranda', icon: 'home' },
      { to: '/student/jadwal', label: 'Jadwal', icon: 'class' },
      { to: '/student/nilai', label: 'Nilai', icon: 'report' },
      { to: '/student/poin', label: 'Poin', icon: 'violation' },
      { to: '/student/profil', label: 'Profil', icon: 'settings' }
    ]
  }
  if (role === 'teacher' || role === 'staff') {
    const items = [
      { to: '/teacher/dashboard', label: 'Beranda', icon: 'home' },
      { to: '/teacher/profile', label: 'Profil', icon: 'settings' },
      { to: '/class', label: 'Kelas', icon: 'class' }
    ]
    if (canAccessModule('student')) items.push({ to: '/student', label: 'Siswa', icon: 'student' })
    if (canAccessModule('violation')) items.push({ to: '/violation', label: 'Pelanggaran', icon: 'violation' })
    if (canAccessModule('ppdb')) items.push({ to: '/ppdb', label: 'PPDB', icon: 'student' })
    if (canAccessModule('counseling')) items.push({ to: '/counseling', label: 'Konseling', icon: 'counseling' })
    if (canAccessModule('extracurricular')) items.push({ to: '/extracurricular', label: 'Ekskul', icon: 'extracurricular' })
    if (canAccessModule('report')) items.push({ to: '/report', label: 'Laporan', icon: 'report' })
    if (canAccessModule('library')) items.push({ to: '/library', label: 'Perpustakaan', icon: 'library' })
    if (canAccessModule('teaching_journal')) items.push({ to: '/teaching-journal', label: 'Jurnal', icon: 'journal' })
    if (canAccessModule('attendance')) items.push({ to: '/attendance/employee', label: 'Absensi', icon: 'attendance' })
    if (canAccessModule('grade_book')) items.push({ to: '/grade-book', label: 'Nilai', icon: 'grade' })
    if (canAccessModule('online_exam')) items.push({ to: '/ujian-online/exams', label: 'Ujian', icon: 'grade' })
    if (canAccessModule('correspondence')) items.push({ to: '/correspondence', label: 'Surat', icon: 'correspondence' })
    return items.slice(0, 5)
  }
  // Admin sekolah: 5 item tetap (Beranda, Pelanggaran, Laporan, Surat, Buku Tamu)
  const items = []
  items.push({ to: '/dashboard', label: 'Beranda', icon: 'home' })
  if (canAccessModule('violation')) items.push({ to: '/violation', label: 'Pelanggaran', icon: 'violation' })
  if (canAccessModule('report')) items.push({ to: '/report', label: 'Laporan', icon: 'report' })
  if (canAccessModule('correspondence')) items.push({ to: '/correspondence', label: 'Surat', icon: 'correspondence' })
  if (canAccessModule('guest_book')) items.push({ to: '/buku-tamu', label: 'Buku Tamu', icon: 'guest' })
  return items
})

const isBottomNavActive = (path) => {
  if (path === '/dashboard') return route.path === '/dashboard'
  if (path === '/super-admin/dashboard') return route.path === '/super-admin/dashboard'
  if (path === '/teacher/dashboard') return route.path === '/teacher/dashboard'
  if (path === '/student/dashboard') return route.path === '/student/dashboard'
  if (path === '/student/poin') return route.path === '/student/poin'
  if (path === '/student/profil') return route.path === '/student/profil'
  return route.path.startsWith(path)
}

const pageTitle = computed(() => {
  const titles = {
    Dashboard: 'Dashboard',
    TeacherDashboard: 'Dashboard Guru',
    StudentDashboard: 'Dashboard Siswa',
    StudentSchedule: 'Jadwal Saya',
    StudentGrades: 'Nilai Saya',
    StudentViolations: 'Pelanggaran & Prestasi',
    StudentCounseling: 'Konseling',
    StudentExtracurricular: 'Ekstrakurikuler',
    StudentPoints: 'Poin Saya',
    StudentChangeRequests: 'Permintaan Perubahan',
    StudentProfile: 'Profil Saya',
    SuperAdminDashboard: 'Dashboard Super Admin',
    AppBranding: 'Branding Aplikasi',
    Institution: authStore.user?.role === 'super_admin' ? 'Kelola Institusi' : 'Profil Instansi',
    Student: 'Data Siswa',
    Teacher: 'Data Guru',
    Facility: 'Sarana Prasarana',
    Lab: 'Manajemen Lab',
    Inventory: 'Inventaris',
    Class: 'Kelas',
    Report: 'Laporan & Statistik',
    AcademicYear: 'Tahun Ajaran',
    InstitutionChangeRequests: 'Request Perubahan',
    StudentChangeRequestsAdmin: 'Permintaan Perubahan Siswa',
    ModuleAccess: 'Akses Modul',
    StudentMutation: 'Mutasi Siswa',
    Notifications: 'Notifikasi',
    AccountSettings: 'Pengaturan Akun',
    AuditLog: 'Audit Log',
    Alumni: 'Alumni',
    NaikKelas: 'Naik Kelas',
    Violation: 'Pelanggaran',
    Counseling: 'Konseling',
    StudentAttendance: 'Absensi Saya',
    Extracurricular: 'Ekstrakurikuler',
    LessonSchedule: 'Jadwal Pelajaran',
    TeachingJournal: 'Jurnal Mengajar',
    AttendanceStudent: 'Absensi Siswa',
    AttendanceEmployee: 'Absensi Guru & Staff',
    GradeBook: 'Buku Nilai',
    Raport: 'Raport Siswa',
    OnlineExamList: 'Ujian Online',
    OnlineExamCreate: 'Buat Ujian',
    OnlineExamDetail: 'Detail Ujian',
    OnlineExamEdit: 'Edit Ujian',
    OnlineExamSessions: 'Sesi Ujian',
    OnlineExamSessionDetail: 'Detail Sesi Ujian',
    OnlineExamBank: 'Bank Soal',
    OnlineExamBankStimulus: 'Stimulus Soal',
    ExamTake: 'Ikuti Ujian',
    Correspondence: 'Persuratan',
    DigitalArchive: 'Arsip Digital',
    BukuTamu: 'Buku Tamu',
    DocumentPickup: 'Pengambilan Ijazah',
    Library: 'Perpustakaan'
  }
  return titles[route.name] || 'Dashboard'
})

const showNotificationBell = computed(() => {
  const role = authStore.user?.role
  if (role === 'super_admin') return false
  if (role === 'student') return !!authStore.user?.student_profile
  return !!authStore.user?.institution_id
})

const unreadNotificationCount = ref(0)
async function fetchUnreadNotificationCount() {
  if (authStore.user?.role === 'super_admin') return
  if (!authStore.user) return
  try {
    const res = await import('@/api/notifications').then(m => m.notificationsApi.getUnreadCount())
    unreadNotificationCount.value = res.data?.count ?? 0
  } catch {
    unreadNotificationCount.value = 0
  }
}

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

const closeSidebar = () => {
  sidebarOpen.value = false
}


// Close sidebar when route changes (mobile)
const handleRouteChange = () => {
  if (window.innerWidth <= MOBILE_BREAKPOINT) {
    closeSidebar()
  }
  userMenuOpen.value = false
}

function closeUserMenuOnClickOutside(e) {
  if (userMenuOpen.value && userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    userMenuOpen.value = false
  }
}

// Handle window resize: tutup sidebar saat ke desktop, pastikan tertutup saat ke mobile
const handleResize = () => {
  if (window.innerWidth > MOBILE_BREAKPOINT) {
    sidebarOpen.value = false
    document.body.style.overflow = ''
  } else {
    document.body.style.overflow = sidebarOpen.value ? 'hidden' : ''
  }
}

let notificationPollInterval = null
onMounted(() => {
  if (window.innerWidth <= MOBILE_BREAKPOINT) {
    sidebarOpen.value = false
  }
  router.afterEach(handleRouteChange)
  window.addEventListener('resize', handleResize)
  document.addEventListener('click', closeUserMenuOnClickOutside)
  fetchUnreadNotificationCount()
  notificationPollInterval = setInterval(fetchUnreadNotificationCount, 60000)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  document.removeEventListener('click', closeUserMenuOnClickOutside)
  document.body.style.overflow = ''
  if (notificationPollInterval) clearInterval(notificationPollInterval)
})

watch(sidebarOpen, (open) => {
  if (window.innerWidth <= MOBILE_BREAKPOINT) {
    document.body.style.overflow = open ? 'hidden' : ''
  }
})

const handleLogout = async () => {
  userMenuOpen.value = false
  await authStore.logout()
}
</script>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
}

.sidebar {
  width: 280px;
  min-width: 280px;
  left: 0 !important;
  top: 0 !important;
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
  color: white;
  display: flex !important;
  flex-direction: column;
  position: fixed !important;
  height: 100vh;
  overflow-y: auto;
  box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
  z-index: 1000;
  visibility: visible;
  opacity: 1;
  transform: none; /* desktop: selalu tampil di kiri */
}

.logo {
  padding: 18px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.logo-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.logo-text h2 {
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  color: #ffffff;
  letter-spacing: -0.5px;
}

.logo-text p {
  font-size: 11px;
  color: #94a3b8;
  margin: 0;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.nav-menu {
  list-style: none;
  padding: 10px 10px;
  flex: 1;
  margin: 0;
  min-height: 0;
  overflow-y: auto;
}

.nav-menu li {
  margin-bottom: 2px;
}

.nav-menu li.nav-group {
  margin-bottom: 2px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  color: #cbd5e1;
  text-decoration: none;
  transition: all 0.2s ease;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  position: relative;
}

.nav-icon {
  flex-shrink: 0;
  width: 20px;
  min-width: 20px;
  height: 20px;
  display: block;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.nav-item .nav-icon,
.nav-group-head .nav-icon {
  flex-shrink: 0;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  transform: translateX(2px);
}

.nav-item.router-link-active {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.nav-item.router-link-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 60%;
  background: white;
  border-radius: 0 3px 3px 0;
}

/* Collapsible group */
.nav-group-head {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  color: #cbd5e1;
  background: none;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
}

.nav-group-head:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

.nav-group-head--active {
  color: #86efac;
  background: rgba(5, 150, 105, 0.2);
}

.nav-group-chevron {
  margin-left: auto;
  flex-shrink: 0;
  transition: transform 0.25s ease;
}

.nav-group-chevron--open {
  transform: rotate(180deg);
}

.nav-group-body {
  overflow: hidden;
  padding-left: 8px;
  border-left: 1px solid rgba(255, 255, 255, 0.1);
  margin-left: 12px;
  margin-top: 2px;
  margin-bottom: 4px;
}

.nav-subitem {
  display: block;
  padding: 8px 12px;
  color: #94a3b8;
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  border-radius: 6px;
  transition: all 0.2s ease;
  margin-bottom: 1px;
}

.nav-subitem:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.06);
}

.nav-subitem--active {
  color: #ffffff;
  background: linear-gradient(135deg, rgba(5, 150, 105, 0.35) 0%, rgba(4, 120, 87, 0.35) 100%);
}

.nav-subitem--active:hover {
  background: linear-gradient(135deg, rgba(5, 150, 105, 0.45) 0%, rgba(4, 120, 87, 0.45) 100%);
}

/* Accordion expand/collapse transition */
.nav-group-enter-active,
.nav-group-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.nav-group-enter-from,
.nav-group-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.user-section {
  padding: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.2);
  position: relative;
}

.user-menu-trigger {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  cursor: pointer;
  color: inherit;
  text-align: left;
  transition: all 0.2s ease;
}

.user-menu-trigger:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.12);
}

.user-menu-trigger:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.user-details {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-weight: 600;
  font-size: 14px;
  margin: 0 0 2px 0;
  color: #ffffff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 11px;
  color: #94a3b8;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-menu-chevron {
  flex-shrink: 0;
  color: #94a3b8;
  transition: transform 0.2s ease;
}

.user-menu-chevron--open {
  transform: rotate(180deg);
}

.user-menu-dropdown {
  position: absolute;
  left: 20px;
  right: 20px;
  bottom: 100%;
  margin-bottom: 8px;
  background: #1e293b;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
  overflow: hidden;
  z-index: 50;
}

.user-menu-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  color: #e2e8f0;
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  background: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: inherit;
  text-align: left;
}

.user-menu-item:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
}

.user-menu-item--active {
  background: rgba(5, 150, 105, 0.2);
  color: #6ee7b7;
}

.user-menu-item--logout {
  color: #fca5a5;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.user-menu-item--logout:hover {
  background: rgba(239, 68, 68, 0.15);
  color: #fecaca;
}

/* Dropdown transition */
.user-menu-enter-active,
.user-menu-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.user-menu-enter-from,
.user-menu-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

.main-content {
  flex: 1;
  margin-left: 280px;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: #f8fafc;
}

.topbar {
  background: white;
  padding: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.topbar-content {
  padding: 24px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.topbar h1 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  letter-spacing: -0.5px;
}

.notification-bell {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  color: #64748b;
  text-decoration: none;
  border-radius: 8px;
  transition: color 0.2s;
}
.notification-bell:hover {
  color: #059669;
}
.notification-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  font-size: 11px;
  font-weight: 600;
  line-height: 18px;
  text-align: center;
  color: #fff;
  background: #dc2626;
  border-radius: 9px;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #64748b;
}

.breadcrumb .separator {
  color: #cbd5e1;
}

.breadcrumb .current {
  color: #1e293b;
  font-weight: 600;
}

.content {
  flex: 1;
  padding: 32px;
  max-width: 1600px;
  width: 100%;
  margin: 0;
  background: #f8fafc;
  min-height: 0;
}

/* Mobile Menu Button */
.mobile-menu-btn {
  display: none;
  position: fixed;
  top: 16px;
  left: 16px;
  z-index: 1001;
  width: 44px;
  height: 44px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.mobile-menu-btn:hover {
  background: #f8fafc;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.mobile-menu-btn:active {
  transform: scale(0.95);
}

.mobile-menu-btn svg {
  color: #1e293b;
}

/* Sidebar Overlay */
.sidebar-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 999;
  cursor: pointer;
  animation: sidebarOverlayFadeIn 0.2s ease;
}

@keyframes sidebarOverlayFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Bottom Navigation (mobile only - admin sekolah) */
.bottom-nav {
  display: none;
}

@media (max-width: 768px) {
  .bottom-nav {
    display: flex;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 998;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    padding: 8px 0;
    padding-bottom: calc(8px + env(safe-area-inset-bottom, 0));
    justify-content: space-around;
    align-items: center;
    gap: 4px;
  }

  .bottom-nav-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 8px 4px;
    color: #64748b;
    text-decoration: none;
    font-size: 11px;
    font-weight: 500;
    border-radius: 10px;
    transition: all 0.2s ease;
    min-height: 52px;
  }

  .bottom-nav-item:hover {
    color: #059669;
    background: rgba(5, 150, 105, 0.08);
  }

  .bottom-nav-item-active {
    color: #059669;
    background: rgba(5, 150, 105, 0.12);
  }

  .bottom-nav-item-active .bottom-nav-label {
    font-weight: 600;
  }

  .bottom-nav-icon {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .bottom-nav-icon svg {
    flex-shrink: 0;
  }

  .bottom-nav-label {
    line-height: 1.2;
    text-align: center;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .layout:has(.bottom-nav) .content {
    padding-bottom: calc(72px + env(safe-area-inset-bottom, 0));
  }
}

/* Responsive: sembunyikan sidebar hanya di mobile (≤768px), tampilkan tombol menu */
@media (max-width: 768px) {
  .layout {
    overflow-x: hidden;
  }

  .mobile-menu-btn {
    display: flex !important;
  }

  .sidebar-overlay {
    display: block !important;
  }

  /* Sidebar tertutup: tidak ambil ruang di flex layout agar konten tidak geser */
  .sidebar {
    transform: translateX(-100%) !important;
    transition: transform 0.3s ease;
    flex: 0 0 0 !important;
    width: 0 !important;
    min-width: 0 !important;
  }

  .sidebar.sidebar-open {
    transform: translateX(0) !important;
    width: 280px !important;
    min-width: 280px !important;
  }

  /* Konten full width saat sidebar tersembunyi - tidak geser ke kanan */
  .main-content {
    margin-left: 0 !important;
    width: 100%;
    min-width: 0;
    max-width: 100%;
  }

  .topbar {
    padding-left: 56px;
  }
}

@media (max-width: 768px) {
  .mobile-menu-btn {
    top: 12px;
    left: 12px;
    width: 40px;
    height: 40px;
  }

  .sidebar {
    width: 280px;
  }

  .topbar {
    padding-left: 56px;
  }

  .topbar-content {
    padding: 12px 16px;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .topbar h1 {
    font-size: 20px;
  }

  .breadcrumb {
    font-size: 11px;
  }

  .content {
    padding: 16px 12px;
    padding-left: max(12px, env(safe-area-inset-left));
    padding-right: max(12px, env(safe-area-inset-right));
  }

  .logo {
    padding: 20px 16px;
  }

  .logo-text h2 {
    font-size: 20px;
  }

  .nav-item {
    padding: 10px 14px;
    font-size: 13px;
  }

  .user-section {
    padding: 16px;
  }
}

@media (max-width: 480px) {
  .sidebar {
    width: 100%;
    max-width: 320px;
  }

  .topbar h1 {
    font-size: 18px;
  }

  .content {
    padding: 12px;
    padding-left: max(12px, env(safe-area-inset-left));
    padding-right: max(12px, env(safe-area-inset-right));
  }

  .logo-text h2 {
    font-size: 18px;
  }

  .logo-text p {
    font-size: 10px;
  }
}

/* Tablet specific adjustments */
@media (min-width: 769px) and (max-width: 1024px) {
  .content {
    padding: 24px;
  }

  .topbar-content {
    padding: 20px 24px;
  }
}
</style>
