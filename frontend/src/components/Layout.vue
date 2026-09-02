<template>
  <div class="layout" :class="{ 'layout-super-admin': isSuperAdminLayout }">
    <div v-if="authStore.isImpersonating" class="impersonation-banner">
      <div class="impersonation-banner-inner">
        <span>
          Mode support: menyamar sebagai <strong>{{ authStore.user?.name }}</strong>
          <template v-if="authStore.activeInstitution?.name || authStore.user?.institution?.name">
            ({{ authStore.activeInstitution?.name || authStore.user?.institution?.name }})
          </template>
          — kembali sebagai {{ authStore.user?.impersonation?.admin_name || 'Super Admin' }}
        </span>
        <button type="button" class="impersonation-exit-btn" :disabled="stoppingImpersonation" @click="handleStopImpersonation">
          {{ stoppingImpersonation ? 'Mengembalikan...' : 'Keluar Impersonate' }}
        </button>
      </div>
    </div>
    <div v-else-if="authStore.isDemoInstitution" class="demo-school-banner">
      <div class="demo-school-banner-inner">
        <span>
          Mode demo: <strong>{{ authStore.activeInstitution?.name || 'SMA 1 Demo Servrin' }}</strong>
          — data fiktif, di-reset setiap hari pukul 03:00 WIB. Jangan masukkan data asli.
        </span>
        <router-link to="/register" class="demo-register-link">Daftar sekolah sendiri</router-link>
      </div>
    </div>

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
        <div class="logo-brand">
          <div class="logo-icon">
            <AppLogo :size="32" />
          </div>
          <div class="logo-text">
            <h2>{{ appName }}</h2>
          </div>
        </div>
        <p v-if="academicPeriodText" class="logo-period">{{ academicPeriodText }}</p>
      </div>

      <div class="sidebar-toolbar">
        <button type="button" class="sidebar-search-btn" @click="openMenuSearch">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" />
            <path d="M20 20L16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
          <span class="sidebar-search-label">Cari menu...</span>
          <kbd class="sidebar-search-kbd">Ctrl+K</kbd>
        </button>
        <button
          type="button"
          class="sidebar-accordion-btn"
          :class="{ 'sidebar-accordion-btn--active': accordionEnabled }"
          :title="`Grup menu: ${accordionModeLabel}`"
          :aria-label="`Mode grup menu: ${accordionModeLabel}`"
          @click="cycleAccordionMode"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 6H20M4 12H14M4 18H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </button>
      </div>
      
      <ul ref="navMenuRef" class="nav-menu" @scroll="onNavMenuScroll">
        <template v-if="pinnedMenuItems.length">
          <li class="nav-divider nav-divider--compact" aria-hidden="true">
            <span>Favorit</span>
          </li>
          <li v-for="item in pinnedMenuItems" :key="`pin-${item.pinId}`" class="nav-pin-item">
            <router-link :to="item.to" class="nav-item nav-item--pinned">
              <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z" fill="currentColor" />
              </svg>
              <span class="nav-item-label">
                <span class="nav-pin-label">{{ item.label }}</span>
                <span v-if="item.groupLabel" class="nav-pin-sublabel">{{ item.groupLabel }}</span>
              </span>
            </router-link>
            <button
              type="button"
              class="nav-pin-remove"
              title="Lepas pin"
              aria-label="Lepas pin"
              @click.stop="removePin(item.pinId)"
            >
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
              </svg>
            </button>
          </li>
        </template>
        <template v-for="entry in displayMenuEntries" :key="entry.key">
          <li v-if="entry.type === 'divider'" class="nav-divider" aria-hidden="true">
            <span>{{ entry.label }}</span>
          </li>
          <li v-else-if="entry.type === 'link'" class="nav-link-row">
            <router-link :to="entry.to" class="nav-item">
              <component :is="entry.icon" />
              <span class="nav-item-label">{{ entry.label }}</span>
              <span v-if="entry.maturity === 'beta'" class="nav-badge">Beta</span>
              <span v-if="entry.badgeCount" class="nav-count-badge">{{ entry.badgeCount }}</span>
            </router-link>
            <button
              type="button"
              class="nav-pin-toggle"
              :class="{ 'nav-pin-toggle--on': isPinned(entry.to) }"
              :title="isPinned(entry.to) ? 'Lepas pin' : 'Pin ke favorit'"
              :aria-label="isPinned(entry.to) ? 'Lepas pin' : 'Pin ke favorit'"
              @click.stop="handleTogglePin(entry.to)"
            >
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path
                  d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"
                  :fill="isPinned(entry.to) ? 'currentColor' : 'none'"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linejoin="round"
                />
              </svg>
            </button>
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
              <span class="nav-item-label">{{ entry.label }}</span>
              <span v-if="entry.maturity === 'beta'" class="nav-badge">Beta</span>
              <svg class="nav-group-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" :class="{ 'nav-group-chevron--open': isGroupExpanded(entry.key) }">
                <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <Transition name="nav-group">
              <div v-show="isGroupExpanded(entry.key)" class="nav-group-body">
                <div
                  v-for="child in entry.visibleChildren"
                  :key="child.to"
                  class="nav-subitem-row"
                >
                  <router-link
                    :to="child.to"
                    class="nav-subitem"
                    active-class=""
                    exact-active-class=""
                    :class="{ 'nav-subitem--active': child.active }"
                    :aria-current="child.active ? 'page' : undefined"
                  >
                    <span>{{ child.label }}</span>
                    <span v-if="child.badgeCount" class="nav-count-badge">{{ child.badgeCount }}</span>
                  </router-link>
                  <button
                    type="button"
                    class="nav-pin-toggle nav-pin-toggle--sub"
                    :class="{ 'nav-pin-toggle--on': isPinned(child.to) }"
                    :title="isPinned(child.to) ? 'Lepas pin' : 'Pin ke favorit'"
                    :aria-label="isPinned(child.to) ? 'Lepas pin' : 'Pin ke favorit'"
                    @click.stop="handleTogglePin(child.to)"
                  >
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path
                        d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"
                        :fill="isPinned(child.to) ? 'currentColor' : 'none'"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linejoin="round"
                      />
                    </svg>
                  </button>
                </div>
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
            <p
              v-if="authStore.activeInstitution?.name && authStore.user?.role !== 'super_admin'"
              class="user-institution"
              :class="{ 'user-institution-non-induk': authStore.activeAffiliation === 'non_induk' }"
            >
              {{ authStore.activeInstitution.name }}
              <template v-if="authStore.activeAffiliation === 'non_induk'"> · Non-Induk</template>
            </p>
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

    <MenuSearchPalette
      v-model:open="menuSearchOpen"
      :items="searchableMenuItems"
      :is-pinned="isPinned"
      :toggle-pin="togglePin"
      @pin-limit="onPinLimit"
    />
    
    <main class="main-content">
      <header class="topbar">
        <div class="topbar-content">
          <h1 v-if="showTopbarTitle">{{ pageTitle }}</h1>
          <div class="topbar-actions">
            <div
              v-if="authStore.canSwitchInstitution"
              class="institution-switcher"
            >
              <label class="institution-switcher-label" for="active-institution-select">Sekolah</label>
              <select
                id="active-institution-select"
                class="institution-switcher-select"
                :value="authStore.activeInstitutionId || ''"
                :disabled="switchingInstitution"
                @change="handleSwitchInstitution"
              >
                <option
                  v-for="inst in authStore.availableInstitutions"
                  :key="inst.id"
                  :value="inst.id"
                >
                  {{ inst.name }}{{ inst.affiliation === 'non_induk' ? ' (Non-Induk)' : ' (Induk)' }}
                </option>
              </select>
            </div>
            <span
              v-else-if="authStore.activeInstitution?.name && authStore.user?.role !== 'super_admin'"
              class="institution-chip"
              :class="{ 'institution-chip-non-induk': authStore.activeAffiliation === 'non_induk' }"
              :title="authStore.activeAffiliation === 'non_induk' ? 'Penugasan non-induk' : 'Sekolah induk'"
            >
              {{ authStore.activeInstitution.name }}
            </span>
            <span
              v-if="showTopbarDivider"
              class="topbar-divider"
              aria-hidden="true"
            />
            <div
              v-if="showNotificationBell"
              ref="notificationBellRef"
              class="notification-bell-wrap"
            >
              <button
                type="button"
                class="notification-bell"
                :class="{ 'notification-bell--active': notificationPanelOpen }"
                title="Notifikasi"
                aria-label="Notifikasi"
                :aria-expanded="notificationPanelOpen"
                @click.stop="toggleNotificationPanel"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span v-if="unreadNotificationCount > 0" class="notification-bell-badge">{{ unreadNotificationCount > 99 ? '99+' : unreadNotificationCount }}</span>
              </button>
              <Transition name="notification-panel">
                <NotificationPanel
                  v-if="notificationPanelOpen"
                  @navigate="closeNotificationPanel"
                />
              </Transition>
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
import { isVocationalLevel, getActiveInstitutionLevel } from '@/utils/institution'
import { hasModuleAccess as userHasModuleAccess, isInstitutionModuleHidden } from '@/utils/moduleAccess'
import { resolvePageTitle, routeHasPageHeading } from '@/utils/pageTitles'
import { INVENTORY_SIDEBAR_ITEMS, canAccessInventoryTab } from '@/composables/inventory/inventoryRoutes'
import {
  readExpandedGroups,
  readSidebarScrollTop,
  scrollActiveNavItemIntoView,
  writeExpandedGroups,
  writeSidebarScrollTop,
} from '@/composables/useSidebarState'
import { useSidebarPins } from '@/composables/useSidebarPins'
import { useSidebarAccordion } from '@/composables/useSidebarAccordion'
import { flattenMenuEntries } from '@/utils/sidebarMenu'
import ErrorBoundary from '@/components/ErrorBoundary.vue'
import AppLogo from '@/components/AppLogo.vue'
import MenuSearchPalette from '@/components/MenuSearchPalette.vue'
import NotificationPanel from '@/components/NotificationPanel.vue'
import { useNotifications } from '@/composables/useNotifications'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const isSuperAdminLayout = computed(() => authStore.user?.role === 'super_admin')
const isVocationalInstitution = computed(() => isVocationalLevel(getActiveInstitutionLevel(authStore)))
const stoppingImpersonation = ref(false)
const switchingInstitution = ref(false)

const academicPeriodText = computed(() => {
  const inst = authStore.activeInstitution || authStore.user?.institution
  if (!inst) return ''
  const year = inst.active_academic_year?.name || inst.active_academic_year?.code
  const semester = inst.active_semester?.name
  if (!year && !semester) return ''
  const parts = []
  if (year) parts.push(`TP ${year}`)
  if (semester) parts.push(semester)
  return parts.join(' · ')
})

async function handleStopImpersonation() {
  if (stoppingImpersonation.value) return
  stoppingImpersonation.value = true
  try {
    await authStore.stopImpersonate()
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e?.message || 'Gagal keluar dari impersonate')
  } finally {
    stoppingImpersonation.value = false
  }
}

async function handleSwitchInstitution(event) {
  const nextId = Number(event?.target?.value)
  if (!nextId || nextId === Number(authStore.activeInstitutionId)) return
  if (switchingInstitution.value) return

  switchingInstitution.value = true
  try {
    await authStore.switchInstitution(nextId)
    // Full reload ke dashboard agar menu/permission/data benar-benar mengikuti sekolah aktif
    const role = authStore.user?.role
    const home = role === 'super_admin'
      ? '/super-admin/dashboard'
      : (role === 'teacher' || role === 'staff')
        ? '/teacher/dashboard'
        : role === 'student'
          ? '/student/dashboard'
          : role === 'parent'
            ? '/parent/dashboard'
            : '/dashboard'
    window.location.assign(home)
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e?.message || 'Gagal mengganti sekolah')
    if (event?.target) {
      event.target.value = String(authStore.activeInstitutionId || '')
    }
  } finally {
    switchingInstitution.value = false
  }
}

const MOBILE_BREAKPOINT = 768
const sidebarOpen = ref(false)
const userMenuOpen = ref(false)
const userMenuRef = ref(null)
const navMenuRef = ref(null)
const expandedGroups = ref(readExpandedGroups())
const menuSearchOpen = ref(false)
const { pinnedIds, isPinned, togglePin, removePin, MAX_PINS } = useSidebarPins(() => authStore.user?.id)
const { accordionEnabled, accordionModeLabel, cycleAccordionMode } = useSidebarAccordion()

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
  h('path', { d: 'M21 8V21H3V8', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M23 3H1V8H23V3Z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M10 12H14', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconCorrespondence = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
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
const IconUks = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M12 2v20', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round' }),
  h('path', { d: 'M2 12h20', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round' }),
  h('rect', { x: 4, y: 4, width: 16, height: 16, rx: 3, stroke: 'currentColor', 'stroke-width': 2, fill: 'none' })
])
const IconLayanan = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
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
const IconReport = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M18 20V10M12 20V4M6 20V14', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconFeedback = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const openFeedbackCount = ref(0)
const pendingPasswordResetCount = ref(0)
const IconExam = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5C15 6.10457 14.1046 7 13 7H11C9.89543 7 9 6.10457 9 5Z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M9 12h6M9 16h6', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconPpdb = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('circle', { cx: '9', cy: '7', r: '4', stroke: 'currentColor', 'stroke-width': 2 }),
  h('path', { d: 'M19 8v6M22 11h-6', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconFinance = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('rect', { x: '2', y: '6', width: '20', height: '12', rx: '2', stroke: 'currentColor', 'stroke-width': 2 }),
  h('path', { d: 'M2 10h20', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round' }),
  h('circle', { cx: '12', cy: '14', r: '1.5', fill: 'currentColor' })
])
const IconLibrary = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconInventory = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M3.3 7l8.7 5 8.7-5M12 22V12', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconLab = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M9 3h6', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M10 3v7.4L5.2 18a2 2 0 0 0 1.7 3h10.2a2 2 0 0 0 1.7-3L14 10.4V3', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M8.5 14h7', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' })
])
const IconFacilityAssets = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M3 21H21M5 21V7L13 2V21M19 21V11M9 9V13M13 9V13M17 9V13M9 17V21M13 17V21', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('rect', { x: '2', y: '14', width: '6', height: '5', rx: '1', stroke: 'currentColor', 'stroke-width': 2 }),
  h('path', { d: 'M4 17h2', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round' })
])
const IconIndustry = () => h('svg', { class: 'nav-icon', width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' }, [
  h('path', { d: 'M3 21h18', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round' }),
  h('path', { d: 'M5 21V8l6-3v16', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M11 21V11h4l4 3v7', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
  h('path', { d: 'M7 12h.01M7 16h.01M15 15h.01M15 18h.01', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round' })
])

const canAccessModule = (moduleKey) => {
  if (moduleKey === 'online_exam' && !authStore.isOnlineExamEntitled) {
    return false
  }
  return userHasModuleAccess(authStore.user, moduleKey)
}

const isLabResponsible = () => !!authStore.user?.is_lab_responsible
const isRoomResponsible = () => !!authStore.user?.is_room_responsible

const canSeeInventorySidebarItem = (tab) => canAccessInventoryTab(authStore.user, tab)
const isExtracurricularSupervisor = () => !!authStore.user?.is_extracurricular_supervisor
const canAccessExtracurricular = () =>
  canAccessModule('extracurricular')
  || (!isInstitutionModuleHidden(authStore.user, 'extracurricular') && isExtracurricularSupervisor())
const canAccessPiket = () =>
  canAccessModule('guru_piket')
  || canAccessModule('guru_piket_manage')
  || (
    !isInstitutionModuleHidden(authStore.user, 'guru_piket')
    && (authStore.user?.is_piket_scheduled || authStore.user?.is_piket_on_duty)
  )

const canAccessLabManagement = () =>
  canAccessModule('facility')
  || (!isInstitutionModuleHidden(authStore.user, 'facility') && isLabResponsible())

const canManageLessonSchedule = () => {
  const role = authStore.user?.role
  return canAccessModule('schedule') && role !== 'teacher' && role !== 'staff'
}

function getDashboardTo() {
  const role = authStore.user?.role
  if (role === 'super_admin') return '/super-admin/dashboard'
  if (role === 'teacher' || role === 'staff') return '/teacher/dashboard'
  if (role === 'student') return '/student/dashboard'
  if (role === 'parent') return '/parent/dashboard'
  return '/dashboard'
}

const parentChildren = ref([])
async function fetchParentChildren() {
  if (authStore.user?.role !== 'parent') {
    parentChildren.value = []
    return
  }
  try {
    const { parentApi } = await import('@/api/parent')
    const res = await parentApi.children()
    parentChildren.value = Array.isArray(res.data?.data) ? res.data.data : []
  } catch {
    parentChildren.value = []
  }
}

function pruneNavDividers(items) {
  const out = []
  for (let i = 0; i < items.length; i++) {
    const e = items[i]
    if (e.type !== 'divider') {
      out.push(e)
      continue
    }
    const hasFollowingItem = items.slice(i + 1).some(x => x.type !== 'divider')
    if (!hasFollowingItem || out.length === 0) continue
    if (out[out.length - 1]?.type === 'divider') continue
    out.push(e)
  }
  return out
}

const menuEntries = computed(() => {
  const role = authStore.user?.role
  const path = route.path
  const addVisible = (group) => {
    const visible = (group.children || []).filter(c => c.visible).map(c => {
      const toPath = c.to?.split('?')[0] ?? c.to
      let active = !!toPath && (c.exact ? path === toPath : (path === toPath || path.startsWith(toPath + '/')))
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
      { type: 'link', key: 'feedback', to: '/feedback', label: 'Inbox Feedback', icon: IconFeedback, badgeCount: openFeedbackCount.value || null },
      addVisible({ type: 'group', key: 'sistem', label: 'Sistem', icon: IconSettings, children: [
        { to: '/super-admin/onboard', label: 'Onboarding Sekolah', visible: true },
        { to: '/institution', label: 'Kelola Institusi', visible: true },
        { to: '/super-admin/institution-admins', label: 'Admin Institusi', visible: true, badgeCount: pendingPasswordResetCount.value || null },
        { to: '/academic-year', label: 'Tahun Ajaran', visible: true },
        { to: '/institution-change-requests', label: 'Request Perubahan', visible: true },
        { to: '/super-admin/app-branding', label: 'Branding Aplikasi', visible: true },
        { to: '/super-admin/system-settings', label: 'Pengaturan Sistem', visible: true },
        { to: '/super-admin/monetisasi', label: 'Monetisasi', visible: true }
      ]}),
      addVisible({ type: 'group', key: 'platform', label: 'Platform', icon: IconReport, children: [
        { to: '/super-admin/adoption', label: 'Monitoring Adopsi', visible: true },
        { to: '/super-admin/broadcasts', label: 'Broadcast', visible: true },
        { to: '/super-admin/catatan-rilis', label: 'Catatan Rilis', visible: true },
        { to: '/super-admin/templates', label: 'Library Template Surat', visible: true },
        { to: '/super-admin/reports', label: 'Laporan Agregat', visible: true }
      ]}),
      { type: 'link', key: 'audit-log', to: '/audit-log', label: 'Audit Log', icon: IconAuditLog }
    ]
  }
  if (role === 'student') {
    return [
      { type: 'link', key: 'dashboard', to: '/student/dashboard', label: 'Dashboard', icon: IconDashboard },
      { type: 'link', key: 'student-schedule', to: '/student/jadwal', label: 'Jadwal Saya', icon: IconAcademic },
      { type: 'link', key: 'student-attendance', to: '/student/absensi', label: 'Absensi Saya', icon: IconAttendance },
      { type: 'link', key: 'student-grades', to: '/student/nilai', label: 'Nilai Saya', icon: IconGrade },
      { type: 'link', key: 'student-exam', to: '/ujian-ikuti', label: 'Ikuti Ujian', icon: IconExam },
      { type: 'link', key: 'student-finance', to: '/student/keuangan', label: 'Tagihan', icon: IconFinance },
      { type: 'link', key: 'student-points', to: '/student/poin', label: 'Poin Saya', icon: IconPoints },
      { type: 'link', key: 'student-violations', to: '/student/pelanggaran-prestasi', label: 'Pelanggaran & Prestasi', icon: IconStudents },
      ...(isVocationalInstitution.value
        ? [
            { type: 'link', key: 'student-pkl', to: '/student/pkl', label: 'PKL / Jurnal', icon: IconIndustry },
            { type: 'link', key: 'student-bkk', to: '/student/bkk', label: 'BKK / Lowongan', icon: IconIndustry },
          ]
        : []),
      addVisible({ type: 'group', key: 'student-lainnya', label: 'Lainnya', icon: IconLayanan, children: [
        { to: '/student/ebooks', label: 'Perpustakaan Digital', visible: true },
        { to: '/student/ekstrakurikuler', label: 'Ekstrakurikuler', visible: true },
        { to: '/student/konseling', label: 'Konseling', visible: true },
        { to: '/student/uks', label: 'Kunjungan UKS', visible: true },
      ]}),
      { type: 'link', key: 'student-profile', to: '/student/profil', label: 'Profil Saya', icon: IconSettings }
    ]
  }
  if (role === 'parent') {
    return [
      { type: 'link', key: 'parent-dashboard', to: '/parent/dashboard', label: 'Ringkasan', icon: IconDashboard },
      { type: 'link', key: 'parent-announcements', to: '/parent/pengumuman', label: 'Pengumuman', icon: IconAcademic },
      ...parentChildren.value.map((c) => addVisible({
        type: 'group',
        key: `parent-child-${c.id}`,
        label: c.name || `Anak #${c.id}`,
        icon: IconStudents,
        children: [
          { to: `/parent/anak/${c.id}/jadwal`, label: 'Jadwal', visible: true },
          { to: `/parent/anak/${c.id}/nilai`, label: 'Nilai', visible: true },
          { to: `/parent/anak/${c.id}/absensi`, label: 'Absensi', visible: true },
          { to: `/parent/anak/${c.id}/pelanggaran`, label: 'Pelanggaran', visible: true },
        ],
      })),
    ]
  }
  const isTeacherOrStaff = role === 'teacher' || role === 'staff'
  const homeroomClasses = isTeacherOrStaff ? (authStore.user?.homeroom_classes || []) : []
  const teachingAssignments = isTeacherOrStaff ? (authStore.user?.teaching_assignments || []) : []
  const supervisedExtracurriculars = isTeacherOrStaff ? (authStore.user?.supervised_extracurriculars || []) : []
  const managedLabs = isTeacherOrStaff ? (authStore.user?.managed_labs || []) : []
  const hasHomeroom = homeroomClasses.length > 0
  const hasTeachingAssignments = teachingAssignments.length > 0
  const hasSupervisedEkskul = supervisedExtracurriculars.length > 0
  const hasManagedLabs = managedLabs.length > 0
  // Sembunyikan jurnal/nilai generik jika sudah ada menu Mapel per pair (kurangi duplikasi).
  const showGenericJournalGrade = !hasTeachingAssignments || !isTeacherOrStaff
  const showBkReportInBkGroup = canAccessModule('violation') || canAccessModule('counseling')
    || canAccessModule('bk_report')
  const showRaportInAkademik = canAccessModule('grade_book')

  const waliChildren = hasHomeroom
    ? [
      { to: '/teacher/wali?panel=siswa', label: 'Data Siswa', visible: true },
      { to: '/teacher/wali?panel=absensi', label: 'Absensi', visible: true },
      { to: '/teacher/wali?panel=nilai', label: 'Nilai', visible: true },
      { to: '/teacher/wali?panel=usulan', label: 'Usulan', visible: true },
      { to: '/teacher/wali?panel=jadwal', label: 'Jadwal', visible: true },
      { to: '/teacher/wali?panel=keuangan', label: 'Keuangan', visible: true },
    ]
    : []

  const mapelChildren = teachingAssignments.map((a) => ({
    to: `/teacher/mapel?class_id=${a.class_id}&subject_id=${a.subject_id}`,
    label: a.label || `${a.subject_name} — ${a.class_name}`,
    visible: canAccessModule('grade_book') || canAccessModule('teaching_journal') || canAccessModule('online_exam'),
  }))

  const ekskulChildren = supervisedExtracurriculars.map((e) => ({
    to: `/extracurricular/${e.id}`,
    label: e.name || `Ekskul #${e.id}`,
    visible: true,
  }))
  if (hasSupervisedEkskul && canAccessModule('extracurricular')) {
    ekskulChildren.push({
      to: '/extracurricular',
      label: 'Semua Ekskul',
      visible: true,
      exact: true,
    })
  }

  const labChildren = managedLabs.map((lab) => ({
    to: `/lab/${lab.id}`,
    label: lab.name || `Lab #${lab.id}`,
    visible: true,
  }))
  if (hasManagedLabs || canAccessLabManagement()) {
    labChildren.push({
      to: '/lab',
      label: canAccessModule('facility') ? 'Manajemen Lab' : 'Daftar Lab Saya',
      visible: true,
      exact: true,
    })
  }
  const canSeeLabBooking = !isInstitutionModuleHidden(authStore.user, 'facility')
    && (role === 'admin' || role === 'institution_admin' || role === 'teacher' || role === 'staff' || canAccessModule('facility'))

  labChildren.push({
    to: '/lab-booking',
    label: 'Booking Lab',
    visible: canSeeLabBooking,
  })

  const hasTeacherDailyWork = isTeacherOrStaff && (
    hasHomeroom
    || hasTeachingAssignments
    || hasSupervisedEkskul
    || hasManagedLabs
  )

  const entries = [
    { type: 'link', key: 'dashboard', to: getDashboardTo(), label: 'Dashboard', icon: IconDashboard },
    ...(isTeacherOrStaff && hasTeachingAssignments && (canAccessModule('teaching_journal') || canAccessModule('grade_book'))
      ? [
          { type: 'link', key: 'teacher-today', to: '/teacher/today', label: 'Jam Mengajar Hari Ini', icon: IconAttendance },
          { type: 'link', key: 'teacher-schedule', to: '/teacher/jadwal', label: 'Jadwal Mengajar', icon: IconAcademic },
        ]
      : []),
    ...(hasHomeroom
      ? [addVisible({ type: 'group', key: 'wali-kelas', label: 'Wali Kelas', icon: IconStudents, children: waliChildren })]
      : []),
    ...(hasTeachingAssignments
      ? [addVisible({ type: 'group', key: 'mata-pelajaran', label: 'Mata Pelajaran', icon: IconGrade, children: mapelChildren })]
      : []),
    ...(hasSupervisedEkskul
      ? [addVisible({ type: 'group', key: 'ekskul-saya', label: 'Ekskul Saya', icon: IconStudents, children: ekskulChildren })]
      : []),
    ...(hasManagedLabs
      ? [addVisible({ type: 'group', key: 'lab-saya', label: 'Lab Saya', icon: IconLab, children: labChildren })]
      : []),
    ...(hasTeacherDailyWork
      ? [{ type: 'divider', key: 'ops-divider', label: 'Operasional sekolah' }]
      : []),
    addVisible({ type: 'group', key: 'kesiswaan', label: 'Kesiswaan', icon: IconStudents, children: [
      { to: '/student', label: 'Data Siswa', visible: canAccessModule('student') },
      { to: '/siswa-keluar', label: 'Siswa Keluar', visible: canAccessModule('student') },
      { to: '/student-mutation', label: 'Mutasi', visible: canAccessModule('student') },
      { to: '/naik-kelas', label: 'Naik Kelas', visible: canAccessModule('student') },
      { to: '/luluskan-siswa', label: 'Luluskan', visible: canAccessModule('student') },
      { to: '/alumni', label: 'Alumni', visible: canAccessModule('student') }
    ]}),
    addVisible({ type: 'group', key: 'akademik', label: 'Keguruan', icon: IconAcademic, children: [
      { to: '/teacher-appreciation', label: 'Apresiasi Guru', visible: canAccessModule('teacher_appreciation') || canAccessModule('teacher_violation_report') },
      { to: '/guru-piket', label: 'Guru Piket', visible: canAccessPiket() },
      { to: '/lesson-schedule', label: 'Jadwal Pelajaran', visible: canManageLessonSchedule() },
      { to: '/teaching-journal', label: 'Jurnal Mengajar', visible: canAccessModule('teaching_journal') && showGenericJournalGrade },
      { to: '/qr-attendance/scan', label: 'Scan QR Absensi', visible: canAccessModule('teaching_journal') && !canAccessModule('attendance') },
      { to: '/grade-book', label: 'Buku Nilai', visible: canAccessModule('grade_book') && showGenericJournalGrade },
      { to: '/raport', label: 'Raport Siswa', visible: showRaportInAkademik }
    ]}),
    addVisible({ type: 'group', key: 'kepegawaian', label: 'Kepegawaian', icon: IconAdmin, children: [
      { to: '/teacher', label: 'Data Pegawai', visible: canAccessModule('teacher') },
      { to: '/teacher-mutation', label: 'Mutasi', visible: canAccessModule('teacher') },
      { to: '/kepegawaian', label: 'Cuti, SK & Jabatan', visible: canAccessModule('kepegawaian') },
    ]}),
    addVisible({ type: 'group', key: 'penggajian', label: 'Penggajian', icon: IconFinance, children: [
      { to: '/penggajian/periode', label: 'Periode', visible: canAccessModule('payroll') },
      { to: '/penggajian/komponen', label: 'Komponen', visible: canAccessModule('payroll') },
      { to: '/penggajian/tunjangan-jabatan', label: 'Tunj. Jabatan', visible: canAccessModule('payroll') },
      { to: '/penggajian/profil', label: 'Profil Gaji', visible: canAccessModule('payroll') },
      { to: '/penggajian/proses', label: 'Proses Gaji', visible: canAccessModule('payroll') },
      { to: '/keuangan/pengeluaran', label: 'Pengeluaran Gaji', visible: canAccessModule('payroll') && !canAccessModule('finance') },
    ]}),
    // Grup Absensi hanya untuk modul attendance (TU/admin): pegawai + QR.
    // Guru mapel mengisi absen siswa lewat Jam Mengajar / Hub Mapel / Wali.
    ...(canAccessModule('attendance')
      ? [addVisible({ type: 'group', key: 'absensi', label: 'Absensi', icon: IconAttendance, children: [
          { to: '/attendance/student', label: 'Absensi Siswa', visible: canAccessModule('teaching_journal') },
          { to: '/attendance/employee', label: 'Absensi Guru & Staff', visible: true },
          { to: '/qr-attendance/scan', label: 'Scan QR Absensi', visible: true },
          { to: '/qr-attendance/generate', label: 'Kartu QR Absensi', visible: true },
        ]})]
      : []),
    addVisible({ type: 'group', key: 'ppdb', label: 'PPDB', icon: IconPpdb, children: [
      { to: '/ppdb/ringkasan', label: 'Ringkasan', visible: canAccessModule('ppdb') },
      { to: '/ppdb/konfigurasi', label: 'Konfigurasi', visible: canAccessModule('ppdb') },
      { to: '/ppdb/pendaftar', label: 'Data Pendaftar', visible: canAccessModule('ppdb') },
      { to: '/ppdb/statistik', label: 'Statistik', visible: canAccessModule('ppdb') },
      { to: '/ppdb/pembayaran', label: 'Pembayaran', visible: canAccessModule('ppdb') },
    ]}),
    addVisible({ type: 'group', key: 'bk', label: 'Bimbingan Konseling', icon: IconCounseling, children: [
      { to: '/violation', label: 'Pelanggaran', visible: canAccessModule('violation') },
      { to: '/counseling', label: 'Konseling', visible: canAccessModule('counseling') },
      { to: '/laporan-bk', label: 'Laporan BK', visible: showBkReportInBkGroup }
    ]}),
    addVisible({ type: 'group', key: 'uks', label: 'UKS', icon: IconUks, children: [
      { to: '/uks', label: 'Kunjungan UKS', visible: canAccessModule('uks') },
      { to: '/uks/stok', label: 'Stok Obat', visible: canAccessModule('uks') },
      { to: '/laporan-uks', label: 'Laporan UKS', visible: canAccessModule('uks') },
    ]}),
    addVisible({ type: 'group', key: 'pkl-bkk', label: 'PKL & BKK', icon: IconIndustry, children: [
      { to: '/industry-partners', label: 'Mitra DU/DI', visible: isVocationalInstitution.value && (canAccessModule('pkl') || canAccessModule('bkk')) },
      { to: '/pkl', label: 'PKL / Prakerin', visible: isVocationalInstitution.value && canAccessModule('pkl') },
      { to: '/bkk', label: 'BKK / Bursa Kerja', visible: isVocationalInstitution.value && canAccessModule('bkk') },
    ]}),
    addVisible({ type: 'group', key: 'keuangan', label: 'Keuangan', icon: IconFinance, children: [
      { to: '/keuangan/jenis-biaya', label: 'Jenis Biaya', visible: canAccessModule('finance') },
      { to: '/keuangan/spp', label: 'SPP', visible: canAccessModule('finance') },
      { to: '/keuangan/tagihan', label: 'Tagihan', visible: canAccessModule('finance') },
      { to: '/keuangan/pembayaran', label: 'Pembayaran', visible: canAccessModule('finance') },
      { to: '/keuangan/pengeluaran', label: 'Pengeluaran', visible: canAccessModule('finance') },
      { to: '/keuangan/tunggakan', label: 'Tunggakan', visible: canAccessModule('finance') },
      { to: '/keuangan/laporan', label: 'Laporan', visible: canAccessModule('finance') },
    ]}),
    addVisible({ type: 'group', key: 'correspondence', label: 'Persuratan', icon: IconCorrespondence, children: [
      { to: '/correspondence', label: 'Buat Surat', visible: canAccessModule('correspondence'), exact: true },
      { to: '/correspondence/workflow', label: 'Arsip', visible: canAccessModule('correspondence') },
      { to: '/correspondence/templates', label: 'Template', visible: canAccessModule('correspondence') },
      { to: '/correspondence/kop', label: 'KOP', visible: canAccessModule('correspondence') },
      { to: '/correspondence/tanda-tangan', label: 'TTD & Stempel', visible: canAccessModule('correspondence') }
    ]}),
    addVisible({ type: 'group', key: 'administrasi', label: 'Administrasi', icon: IconAdmin, children: [
      { to: '/academic-calendar', label: 'Kalender Akademik', visible: canAccessModule('academic_calendar') },
      { to: '/school-content', label: 'Berita & Galeri', visible: canAccessModule('school_content') || canAccessModule('institution') },
      { to: '/digital-archive', label: 'Arsip Digital', visible: canAccessModule('digital_archive') },
      { to: '/buku-tamu', label: 'Buku Tamu', visible: canAccessModule('guest_book') },
      { to: '/pengambilan-ijazah', label: 'Pengambilan Ijazah', visible: canAccessModule('document_pickup') },
      { to: '/report', label: 'Laporan', visible: canAccessModule('report') }
    ]}),
    // Flat ekskul hanya jika bukan pembina (atau koordinator tanpa daftar supervised — canAccessExtracurricular)
    ...(!hasSupervisedEkskul && canAccessExtracurricular()
      ? [{ type: 'link', key: 'extracurricular', to: '/extracurricular', label: 'Ekstrakurikuler', icon: IconStudents }]
      : []),
    addVisible({ type: 'group', key: 'fasilitas-aset', label: 'Fasilitas & Aset', icon: IconFacilityAssets, children: [
      { to: '/facility', label: 'Sarana Prasarana', visible: canAccessModule('facility') },
      {
        to: '/lab',
        label: canAccessModule('facility') ? 'Manajemen Lab' : 'Lab Saya',
        visible: !hasManagedLabs && canAccessLabManagement(),
      },
      { to: '/lab-booking', label: 'Booking Lab', visible: !hasManagedLabs && canSeeLabBooking },
    ]}),
    addVisible({
      type: 'group',
      key: 'inventaris',
      label: 'Inventaris',
      icon: IconInventory,
      navPrefix: '/inventory',
      children: INVENTORY_SIDEBAR_ITEMS.map((item) => ({
        to: item.path,
        label: item.label,
        visible: canSeeInventorySidebarItem(item.tab),
      })),
    }),
    ...(canAccessModule('library')
      ? [{ type: 'link', key: 'perpustakaan', to: '/library', label: 'Perpustakaan', icon: IconLibrary }]
      : []),
    addVisible({ type: 'group', key: 'master', label: 'Master Data', icon: IconDatabase, children: [
      { to: '/institution', label: 'Profil Instansi', visible: canAccessModule('institution') },
      { to: '/class', label: 'Kelas', visible: canAccessModule('class') },
      {
        to: '/program-keahlian',
        label: 'Program Keahlian',
        visible: canAccessModule('class') && isVocationalInstitution.value,
      },
    ]}),
    addVisible({ type: 'group', key: 'layanan', label: 'Layanan', icon: IconLayanan, children: [
      { to: '/student-change-requests', label: 'Permintaan Perubahan Siswa', visible: canAccessModule('student') },
      { to: '/teacher-change-requests', label: 'Permintaan Perubahan Guru', visible: canAccessModule('teacher') },
    ]}),
    ...(isTeacherOrStaff
      ? [
          addVisible({ type: 'group', key: 'saya', label: 'Saya', icon: IconSettings, children: [
            { to: '/teacher/profile', label: 'Profil Saya', visible: true },
            { to: '/teacher/cuti', label: 'Cuti Saya', visible: true },
            { to: '/teacher/slip-gaji', label: 'Slip Gaji', visible: true },
            { to: '/teacher/poin', label: 'Poin & Prestasi Saya', visible: true },
            { to: '/feedback', label: 'Lapor Bug / Fitur', visible: true },
          ]}),
        ]
      : []),
    ...(role === 'institution_admin' || role === 'admin'
      ? [
          { type: 'divider', key: 'settings-divider', label: 'Pengaturan' },
          { type: 'link', key: 'feedback', to: '/feedback', label: 'Lapor Bug / Fitur', icon: IconFeedback },
          { type: 'link', key: 'pengaturan', to: '/module-access', label: 'Akses Modul', icon: IconModuleAccess },
          ...(authStore.isMonetizationVisible
            ? [{ type: 'link', key: 'billing', to: '/billing', label: 'Paket & Add-on', icon: IconFinance }]
            : []),
          { type: 'link', key: 'audit-log', to: '/audit-log', label: 'Audit Log', icon: IconAuditLog }
        ]
      : []),
    { type: 'divider', key: 'beta-divider', label: 'Sedang dikembangkan' },
    addVisible({ type: 'group', key: 'ujian-online', label: 'Ujian Online', icon: IconExam, maturity: 'beta', children: [
      { to: '/ujian-online/exams', label: 'Daftar Ujian', visible: canAccessModule('online_exam') },
      { to: '/ujian-online/sesi?fokus=peserta', label: 'Peserta Ujian', visible: canAccessModule('online_exam') },
      { to: '/ujian-online/sesi?fokus=kontrol', label: 'Kontrol Ujian', visible: canAccessModule('online_exam') },
      { to: '/ujian-online/bank-soal', label: 'Bank Soal', visible: canAccessModule('online_exam') }
    ]})
  ]
  const visible = entries.filter(e =>
    e.type === 'divider' ||
    e.type === 'link' ||
    (e.type === 'group' && e.visibleChildren?.length > 0)
  )
  const pruned = pruneNavDividers(visible)
  const opsIdx = pruned.findIndex(e => e.key === 'ops-divider')
  if (opsIdx !== -1) {
    const nextItem = pruned.slice(opsIdx + 1).find(e => e.type !== 'divider')
    if (!nextItem || nextItem.key === 'saya' || nextItem.key === 'ujian-online') {
      return pruneNavDividers(pruned.filter(e => e.key !== 'ops-divider'))
    }
  }
  return pruned
})

const searchableMenuItems = computed(() => flattenMenuEntries(menuEntries.value))

const pinnedMenuItems = computed(() => {
  const byPinId = new Map(searchableMenuItems.value.map((item) => [item.pinId, item]))
  return pinnedIds.value.map((id) => byPinId.get(id)).filter(Boolean)
})

const displayMenuEntries = computed(() => {
  const pinnedTos = new Set(pinnedMenuItems.value.map((item) => item.to))
  const result = []
  for (const entry of menuEntries.value) {
    if (entry.type === 'divider') {
      result.push(entry)
      continue
    }
    if (entry.type === 'link') {
      if (pinnedTos.has(entry.to)) continue
      result.push(entry)
      continue
    }
    if (entry.type === 'group') {
      const children = (entry.visibleChildren || []).filter((c) => !pinnedTos.has(c.to))
      if (!children.length) continue
      result.push({ ...entry, visibleChildren: children })
    }
  }
  return pruneNavDividers(result)
})

function openMenuSearch() {
  menuSearchOpen.value = true
}

function handleTogglePin(pinId) {
  const ok = togglePin(pinId)
  if (!ok) onPinLimit()
}

function onPinLimit() {
  toast.warning('Batas favorit', `Maksimal ${MAX_PINS} menu. Lepas pin lain terlebih dahulu.`)
}

function onGlobalKeydown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    menuSearchOpen.value = !menuSearchOpen.value
  }
}

const hasActiveChild = (entry) => {
  if (entry.type !== 'group' || !entry.children) return false
  const path = route.path
  if (entry.navPrefix && path.startsWith(entry.navPrefix)) return true
  return entry.children.some(c => {
    if (!c.visible) return false
    const toPath = c.to?.split('?')[0] ?? c.to
    return path === c.to || path.startsWith(c.to + '/') || (toPath && (path === toPath || path.startsWith(toPath + '/')))
  })
}
const isGroupExpanded = (key) => expandedGroups.value.has(key)
function persistExpandedGroups() {
  writeExpandedGroups(expandedGroups.value)
}

function onNavMenuScroll() {
  if (navMenuRef.value) {
    writeSidebarScrollTop(navMenuRef.value.scrollTop)
  }
}

function restoreNavMenuScroll() {
  const el = navMenuRef.value
  if (!el) return
  el.scrollTop = readSidebarScrollTop()
}

function toggleGroup(key) {
  const next = new Set(expandedGroups.value)
  if (next.has(key)) {
    next.delete(key)
  } else if (accordionEnabled.value) {
    next.clear()
    next.add(key)
  } else {
    next.add(key)
  }
  expandedGroups.value = next
  persistExpandedGroups()
}
function ensureGroupExpandedForKey(key) {
  if (accordionEnabled.value) {
    if (!expandedGroups.value.has(key) || expandedGroups.value.size !== 1) {
      expandedGroups.value = new Set([key])
      persistExpandedGroups()
    }
    return
  }
  if (!expandedGroups.value.has(key)) {
    expandedGroups.value = new Set([...expandedGroups.value, key])
    persistExpandedGroups()
  }
}

watch(() => route.path, (path) => {
  for (const entry of menuEntries.value) {
    if (entry.type === 'group' && entry.children) {
      const hasActive = (entry.navPrefix && path.startsWith(entry.navPrefix))
        || entry.children.some(c => {
          if (!c.visible) return false
          const toPath = c.to?.split('?')[0] ?? c.to
          return path === c.to || path.startsWith(c.to + '/') || (toPath && (path === toPath || path.startsWith(toPath + '/')))
        })
      if (hasActive) ensureGroupExpandedForKey(entry.key)
    }
  }
  requestAnimationFrame(() => {
    scrollActiveNavItemIntoView(navMenuRef.value)
  })
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
      { to: '/feedback', label: 'Feedback', icon: 'request' },
      { to: '/audit-log', label: 'Audit', icon: 'settings' }
    ]
  }
  if (role === 'student') {
    return [
      { to: '/student/dashboard', label: 'Beranda', icon: 'home' },
      { to: '/student/jadwal', label: 'Jadwal', icon: 'class' },
      { to: '/student/absensi', label: 'Absensi', icon: 'attendance' },
      { to: '/student/nilai', label: 'Nilai', icon: 'report' },
      { to: '/student/profil', label: 'Profil', icon: 'settings' }
    ]
  }
  if (role === 'parent') {
    const items = [
      { to: '/parent/dashboard', label: 'Beranda', icon: 'home' },
      { to: '/parent/pengumuman', label: 'Pengumuman', icon: 'report' }
    ]
    const firstChild = parentChildren.value[0]
    if (firstChild?.id) {
      items.push({ to: `/parent/anak/${firstChild.id}/nilai`, label: 'Nilai', icon: 'class' })
      items.push({ to: `/parent/anak/${firstChild.id}/absensi`, label: 'Absensi', icon: 'attendance' })
    }
    return items.slice(0, 5)
  }
  if (role === 'teacher' || role === 'staff') {
    const items = [
      { to: '/teacher/dashboard', label: 'Beranda', icon: 'home' }
    ]
    const teachingAssignments = authStore.user?.teaching_assignments || []
    const homeroomClasses = authStore.user?.homeroom_classes || []
    if (teachingAssignments.length && (canAccessModule('teaching_journal') || canAccessModule('grade_book'))) {
      items.push({ to: '/teacher/today', label: 'Mengajar', icon: 'attendance' })
    }
    if (homeroomClasses.length) {
      items.push({ to: '/teacher/wali', label: 'Wali', icon: 'class' })
    }
    if (teachingAssignments.length && (canAccessModule('grade_book') || canAccessModule('teaching_journal'))) {
      items.push({ to: '/teacher/mapel', label: 'Mapel', icon: 'report' })
    }
    if (canAccessModule('student')) items.push({ to: '/student', label: 'Siswa', icon: 'student' })
    if (canAccessModule('attendance')) items.push({ to: '/attendance/employee', label: 'Absensi', icon: 'attendance' })
    if (canAccessModule('correspondence')) items.push({ to: '/correspondence', label: 'Surat', icon: 'correspondence' })
    if (canAccessModule('violation')) items.push({ to: '/violation', label: 'Pelanggaran', icon: 'violation' })
    else if (canAccessModule('bk_report')) items.push({ to: '/laporan-bk', label: 'Laporan BK', icon: 'violation' })
    return items.slice(0, 5)
  }
  const items = []
  items.push({ to: '/dashboard', label: 'Beranda', icon: 'home' })
  if (canAccessModule('student')) items.push({ to: '/student', label: 'Siswa', icon: 'student' })
  if (canAccessModule('attendance')) items.push({ to: '/attendance/employee', label: 'Absensi', icon: 'attendance' })
  if (canAccessModule('correspondence')) items.push({ to: '/correspondence', label: 'Surat', icon: 'correspondence' })
  if (canAccessModule('report')) items.push({ to: '/report', label: 'Laporan', icon: 'report' })
  if (canAccessModule('guest_book')) items.push({ to: '/buku-tamu', label: 'Buku Tamu', icon: 'guest' })
  return items.slice(0, 5)
})

const isBottomNavActive = (path) => {
  if (path === '/dashboard') return route.path === '/dashboard'
  if (path === '/super-admin/dashboard') return route.path === '/super-admin/dashboard'
  if (path === '/teacher/dashboard') return route.path === '/teacher/dashboard'
  if (path === '/student/dashboard') return route.path === '/student/dashboard'
  if (path === '/student/absensi') return route.path === '/student/absensi'
  if (path === '/student/profil') return route.path === '/student/profil'
  if (path === '/parent/dashboard') return route.path === '/parent/dashboard'
  return route.path.startsWith(path)
}

const pageTitle = computed(() => resolvePageTitle(route.name, authStore, route))
const showTopbarTitle = computed(() => !routeHasPageHeading(route.name))

watch(pageTitle, (title) => {
  if (typeof document === 'undefined' || route.name === 'SchoolPublic') return
  document.title = title ? `${title} · ${appName}` : appName
}, { immediate: true })

const showNotificationBell = computed(() => {
  const role = authStore.user?.role
  if (role === 'super_admin') return true
  if (role === 'student') return !!authStore.user?.student_profile
  return !!(authStore.activeInstitutionId || authStore.user?.institution_id)
})

const showInstitutionInTopbar = computed(() => {
  if (authStore.canSwitchInstitution) return true
  return !!(authStore.activeInstitution?.name && authStore.user?.role !== 'super_admin')
})

const showTopbarDivider = computed(() => showInstitutionInTopbar.value && showNotificationBell.value)

const { unreadCount: unreadNotificationCount, refreshUnreadCount } = useNotifications()
const toast = useToast()
const notificationPanelOpen = ref(false)
const notificationBellRef = ref(null)

function toggleNotificationPanel() {
  notificationPanelOpen.value = !notificationPanelOpen.value
  if (notificationPanelOpen.value) {
    refreshUnreadCount()
  }
}

function closeNotificationPanel() {
  notificationPanelOpen.value = false
  refreshUnreadCount()
}

async function fetchUnreadNotificationCount() {
  if (!authStore.user) return
  await refreshUnreadCount()
  if (authStore.user?.role === 'super_admin') {
    try {
      const countRes = await import('@/api/feedbackTicket').then(m => m.feedbackTicketApi.getOpenCount())
      openFeedbackCount.value = countRes.data?.count ?? 0
    } catch {
      openFeedbackCount.value = 0
    }
    try {
      const resetRes = await import('@/api/passwordResetRequest').then(m => m.passwordResetRequestApi.getPendingCount())
      pendingPasswordResetCount.value = resetRes.data?.count ?? 0
    } catch {
      pendingPasswordResetCount.value = 0
    }
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
  notificationPanelOpen.value = false
}

function closeUserMenuOnClickOutside(e) {
  if (userMenuOpen.value && userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    userMenuOpen.value = false
  }
  if (notificationPanelOpen.value && notificationBellRef.value && !notificationBellRef.value.contains(e.target)) {
    notificationPanelOpen.value = false
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
  restoreNavMenuScroll()
  requestAnimationFrame(() => {
    scrollActiveNavItemIntoView(navMenuRef.value)
  })
  router.afterEach(handleRouteChange)
  window.addEventListener('resize', handleResize)
  window.addEventListener('keydown', onGlobalKeydown)
  document.addEventListener('click', closeUserMenuOnClickOutside)
  fetchUnreadNotificationCount()
  fetchParentChildren()
  notificationPollInterval = setInterval(fetchUnreadNotificationCount, 60000)
})

onUnmounted(() => {
  onNavMenuScroll()
  persistExpandedGroups()
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('keydown', onGlobalKeydown)
  document.removeEventListener('click', closeUserMenuOnClickOutside)
  document.body.style.overflow = ''
  if (notificationPollInterval) clearInterval(notificationPollInterval)
})

watch(() => authStore.user?.role, fetchParentChildren)
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

.demo-school-banner {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1001;
  background: #065f46;
  color: #ecfdf5;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.demo-school-banner-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 16px;
  font-size: 13px;
  flex-wrap: wrap;
}

.demo-school-banner-inner span {
  min-width: 0;
  flex: 1 1 280px;
}

.demo-register-link {
  flex-shrink: 0;
  color: #fff;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: 8px;
  padding: 6px 12px;
  text-decoration: none;
  font-weight: 600;
  white-space: nowrap;
}

.demo-register-link:hover {
  background: rgba(255, 255, 255, 0.25);
}

.layout:has(.demo-school-banner) .sidebar {
  top: 44px;
  height: calc(100vh - 44px);
}

.layout:has(.demo-school-banner) .main-content {
  padding-top: 44px;
}

.layout:has(.demo-school-banner) .mobile-menu-btn {
  top: calc(12px + 44px);
}

@media (max-width: 768px) {
  .layout:has(.demo-school-banner) .main-content {
    padding-top: calc(56px + 44px);
  }

  .layout:has(.demo-school-banner) .topbar {
    top: 44px;
  }

  .demo-school-banner-inner {
    flex-direction: column;
    align-items: flex-start;
  }
}

.impersonation-banner {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1200;
  background: #f59e0b;
  color: #78350f;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.impersonation-banner-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 16px;
  flex-wrap: wrap;
  font-size: 13px;
}

.impersonation-exit-btn {
  border: none;
  background: #78350f;
  color: #fff;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.impersonation-exit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.layout:has(.impersonation-banner) .sidebar {
  top: 44px;
  height: calc(100vh - 44px);
}

.layout:has(.impersonation-banner) .main-content {
  padding-top: 44px;
}

.layout:has(.impersonation-banner) .mobile-menu-btn {
  top: calc(12px + 44px);
}

@media (max-width: 768px) {
  .layout:has(.impersonation-banner) .main-content {
    padding-top: calc(56px + 44px);
  }

  .layout:has(.impersonation-banner) .topbar {
    top: 44px;
  }
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
  overflow: hidden;
  box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
  z-index: 1000;
  visibility: visible;
  opacity: 1;
  transform: none; /* desktop: selalu tampil di kiri */
}

.logo {
  padding: 12px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-shrink: 0;
}

.logo-brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo-icon {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.logo-text h2 {
  font-size: 18px;
  font-weight: 700;
  margin: 0;
  color: #ffffff;
  letter-spacing: -0.5px;
}

.logo-period {
  margin: 0;
  font-size: 11px;
  line-height: 1.35;
  color: #94a3b8;
  font-weight: 500;
  letter-spacing: 0.2px;
}

.sidebar-toolbar {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 10px 8px;
  flex-shrink: 0;
}

.sidebar-search-btn {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.06);
  color: #94a3b8;
  font-size: 12px;
  font-family: inherit;
  cursor: pointer;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.sidebar-search-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #e2e8f0;
  border-color: rgba(255, 255, 255, 0.16);
}

.sidebar-search-label {
  flex: 1;
  min-width: 0;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-search-kbd {
  flex-shrink: 0;
  font-size: 9px;
  font-family: inherit;
  color: #64748b;
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 4px;
  padding: 1px 4px;
}

.sidebar-accordion-btn {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.06);
  color: #94a3b8;
  cursor: pointer;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.sidebar-accordion-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #e2e8f0;
}

.sidebar-accordion-btn--active {
  color: #6ee7b7;
  border-color: rgba(110, 231, 183, 0.35);
  background: rgba(5, 150, 105, 0.2);
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

.nav-divider {
  margin: 14px 8px 8px;
  padding-top: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  list-style: none;
}

.nav-divider span {
  display: block;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #64748b;
  padding: 0 4px;
}

.nav-divider--compact {
  margin-top: 4px;
  margin-bottom: 4px;
  padding-top: 0;
  border-top: none;
}

.nav-link-row,
.nav-subitem-row,
.nav-pin-item {
  position: relative;
}

.nav-subitem-row {
  display: flex;
  align-items: center;
}

.nav-pin-item {
  display: flex;
  align-items: stretch;
  gap: 2px;
}

.nav-pin-item .nav-item--pinned {
  flex: 1;
  min-width: 0;
}

.nav-pin-label {
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-pin-sublabel {
  display: block;
  font-size: 10px;
  font-weight: 500;
  color: #94a3b8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-pin-remove {
  flex-shrink: 0;
  align-self: center;
  width: 24px;
  height: 24px;
  margin-right: 4px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.15s, background 0.15s, color 0.15s;
}

.nav-pin-item:hover .nav-pin-remove,
.nav-pin-remove:focus-visible {
  opacity: 1;
}

.nav-pin-remove:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fca5a5;
}

.nav-pin-toggle {
  position: absolute;
  right: 6px;
  top: 50%;
  transform: translateY(-50%);
  width: 22px;
  height: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.15s, background 0.15s, color 0.15s;
  z-index: 1;
}

.nav-pin-toggle--sub {
  right: 4px;
  width: 20px;
  height: 20px;
}

.nav-link-row:hover .nav-pin-toggle,
.nav-subitem-row:hover .nav-pin-toggle,
.nav-pin-toggle:focus-visible {
  opacity: 1;
}

.nav-pin-toggle:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
}

.nav-pin-toggle--on {
  opacity: 1;
  color: #fbbf24;
}

.nav-link-row .nav-item,
.nav-subitem-row .nav-subitem {
  padding-right: 30px;
}

.nav-item-label {
  flex: 1;
  min-width: 0;
}

.nav-badge {
  flex-shrink: 0;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #fbbf24;
  background: rgba(251, 191, 36, 0.15);
  border: 1px solid rgba(251, 191, 36, 0.35);
  line-height: 1.4;
}

.nav-count-badge {
  flex-shrink: 0;
  min-width: 18px;
  padding: 1px 6px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 700;
  text-align: center;
  color: #fff;
  background: #dc2626;
  line-height: 1.4;
}

.nav-item.router-link-active .nav-count-badge {
  background: #fff;
  color: #dc2626;
}

.nav-item.router-link-active .nav-badge {
  color: #fef3c7;
  background: rgba(255, 255, 255, 0.18);
  border-color: rgba(255, 255, 255, 0.35);
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  color: #cbd5e1;
  text-decoration: none;
  transition: all 0.2s ease;
  border-radius: 8px;
  font-size: 12px;
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
  gap: 8px;
  width: 100%;
  padding: 8px 10px;
  color: #cbd5e1;
  background: none;
  border: none;
  border-radius: 8px;
  font-size: 12px;
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
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  flex: 1;
  min-width: 0;
  padding: 6px 10px;
  color: #94a3b8;
  text-decoration: none;
  font-size: 12px;
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
  padding: 12px 14px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.2);
  position: relative;
  flex-shrink: 0;
}

.user-menu-trigger {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px;
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
  width: 32px;
  height: 32px;
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
  font-size: 13px;
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

.user-institution {
  font-size: 11px;
  color: #6ee7b7;
  margin: 4px 0 0 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-institution-non-induk {
  color: #fdba74;
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
  padding: 8px 12px;
  color: #e2e8f0;
  text-decoration: none;
  font-size: 12px;
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
  min-width: 0;
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
  padding: 12px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.topbar h1 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  letter-spacing: -0.5px;
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-left: auto;
}

.topbar-divider {
  width: 1px;
  height: 20px;
  background: #e2e8f0;
  flex-shrink: 0;
}

.institution-switcher {
  display: flex;
  align-items: center;
  gap: 8px;
  max-width: min(320px, 46vw);
}

.institution-switcher-label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  white-space: nowrap;
}

.institution-switcher-select {
  min-width: 0;
  flex: 1;
  max-width: 260px;
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #0f172a;
  font-size: 13px;
  font-weight: 500;
}

.institution-switcher-select:disabled {
  opacity: 0.6;
  cursor: wait;
}

.institution-chip {
  display: inline-flex;
  align-items: center;
  max-width: min(240px, 40vw);
  padding: 6px 10px;
  border-radius: 999px;
  background: #ecfdf5;
  color: #047857;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.institution-chip-non-induk {
  background: #fff7ed;
  color: #c2410c;
}

.notification-bell-wrap {
  position: relative;
}

.notification-bell {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  color: #64748b;
  background: transparent;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-family: inherit;
  transition: color 0.15s, background 0.15s;
}
.notification-bell:hover,
.notification-bell--active {
  color: #059669;
  background: #f1f5f9;
}
.notification-bell:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}
.notification-bell-badge {
  position: absolute;
  top: 4px;
  right: 4px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  font-size: 10px;
  font-weight: 600;
  line-height: 16px;
  text-align: center;
  color: #fff;
  background: #dc2626;
  border-radius: 8px;
}

.notification-panel-enter-active,
.notification-panel-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.notification-panel-enter-from,
.notification-panel-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.content {
  flex: 1;
  padding: 20px;
  max-width: 1600px;
  width: 100%;
  min-width: 0;
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
  z-index: 1003;
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
    overflow-x: clip;
  }

  .main-content {
    min-width: 0;
    max-width: 100%;
    /* ruang untuk topbar fixed (judul satu baris) */
    padding-top: 56px;
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
  }

  .topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    padding-left: 56px;
    z-index: 1002;
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
    z-index: 1002;
  }

  .topbar-content {
    padding: 12px 16px;
    flex-direction: row;
    align-items: center;
    gap: 8px;
    min-height: 56px;
    box-sizing: border-box;
  }

  .topbar h1 {
    font-size: 1.0625rem;
    line-height: 1.3;
  }

  .content {
    padding: 14px 12px;
    padding-left: max(12px, env(safe-area-inset-left));
    padding-right: max(12px, env(safe-area-inset-right));
  }

  .logo {
    padding: 14px 16px;
  }

  .logo-text h2 {
    font-size: 16px;
  }

  .sidebar-search-kbd {
    display: none;
  }

  .nav-item {
    padding: 8px 12px;
    font-size: 12px;
  }

  .user-section {
    padding: 12px;
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
    font-size: 16px;
  }

  .logo-period {
    font-size: 10px;
  }
}

/* Laptop 1366x768: sidebar lebih ramping, konten lebih rapat vertikal */
@media (min-width: 769px) and (max-width: 1440px) {
  .sidebar {
    width: 240px;
    min-width: 240px;
  }

  .main-content {
    margin-left: 240px;
  }

  .logo {
    padding: 10px 12px;
  }

  .nav-item,
  .nav-group-head {
    padding: 7px 8px;
    font-size: 12px;
  }

  .nav-subitem {
    padding: 5px 8px;
  }

  .topbar-content {
    padding: 10px 16px;
  }

  .topbar h1 {
    font-size: 16px;
  }

  .content {
    padding: 14px 16px;
  }

  .user-section {
    padding: 10px 12px;
  }
}
</style>
