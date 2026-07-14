<template>
  <div class="app-shell" :class="{ 'sidebar-collapsed': sidebarCollapsed, 'mobile-open': mobileMenuOpen }">

    <!-- ═══════════════════════════════════
         SIDEBAR GAUCHE RÉTRACTABLE
    ═══════════════════════════════════ -->
    <aside class="sidebar" :class="{ 'collapsed': sidebarCollapsed }">

      <!-- En-tête sidebar : toggle -->
      <div class="sidebar-header">
        <button class="sidebar-toggle" @click="toggleSidebar" :title="sidebarCollapsed ? 'Déplier' : 'Réduire'">
          <Icon :name="sidebarCollapsed ? 'chevron-right' : 'chevron-left'" :size="16" />
        </button>
      </div>

      <!-- Séparateur gold -->
      <div class="sidebar-gold-line" />

      <!-- Navigation principale -->
      <nav class="sidebar-nav">
        <div class="sidebar-section-label">NAVIGATION</div>

        <Link :href="route('dashboard')" class="sidebar-item" :class="{ active: $page.url === '/dashboard' }">
          <span class="sidebar-item-icon"><Icon name="chart-pie" :size="20" /></span>
          <span class="sidebar-item-label">Tableau de bord</span>
          <span v-if="sidebarCollapsed" class="sidebar-tooltip">Tableau de bord</span>
        </Link>

        <Link :href="route('projects.index')" class="sidebar-item" :class="{ active: $page.url.startsWith('/projects') }">
          <span class="sidebar-item-icon"><Icon name="briefcase" :size="20" /></span>
          <span class="sidebar-item-label">Projets</span>
          <span v-if="sidebarCollapsed" class="sidebar-tooltip">Projets</span>
        </Link>

        <template v-if="!$page.props.auth.user.roles?.includes('client')">
          <Link :href="route('expenses.index')" class="sidebar-item" :class="{ active: $page.url.startsWith('/expenses') }">
            <span class="sidebar-item-icon"><Icon name="banknotes" :size="20" /></span>
            <span class="sidebar-item-label">Dépenses</span>
            <span v-if="sidebarCollapsed" class="sidebar-tooltip">Dépenses</span>
          </Link>

          <Link :href="route('clients.index')" class="sidebar-item" :class="{ active: $page.url.startsWith('/clients') }">
            <span class="sidebar-item-icon"><Icon name="users" :size="20" /></span>
            <span class="sidebar-item-label">Clients</span>
            <span v-if="sidebarCollapsed" class="sidebar-tooltip">Clients</span>
          </Link>
        </template>

        <template v-if="!$page.props.auth.user.roles?.includes('collaborateur') && !$page.props.auth.user.roles?.includes('client')">
          <Link :href="route('reports.index')" class="sidebar-item" :class="{ active: $page.url.startsWith('/reports') }">
            <span class="sidebar-item-icon"><Icon name="chart-bar" :size="20" /></span>
            <span class="sidebar-item-label">Rapports</span>
            <span v-if="sidebarCollapsed" class="sidebar-tooltip">Rapports</span>
          </Link>
        </template>

        <!-- Admin uniquement -->
        <template v-if="$page.props.auth.user.roles?.includes('admin')">
          <div class="sidebar-divider" />
          <div class="sidebar-section-label">ADMINISTRATION</div>

          <Link :href="route('users.index')" class="sidebar-item" :class="{ active: $page.url.startsWith('/users') }">
            <span class="sidebar-item-icon"><Icon name="user-group" :size="20" /></span>
            <span class="sidebar-item-label">Utilisateurs</span>
            <span v-if="sidebarCollapsed" class="sidebar-tooltip">Utilisateurs</span>
          </Link>

          <Link :href="route('categories.index')" class="sidebar-item" :class="{ active: $page.url.startsWith('/categories') }">
            <span class="sidebar-item-icon"><Icon name="tag" :size="20" /></span>
            <span class="sidebar-item-label">Catégories</span>
            <span v-if="sidebarCollapsed" class="sidebar-tooltip">Catégories</span>
          </Link>

          <Link :href="route('activity-logs.index')" class="sidebar-item" :class="{ active: $page.url.startsWith('/activity-logs') }">
            <span class="sidebar-item-icon"><Icon name="clipboard-document-list" :size="20" /></span>
            <span class="sidebar-item-label">Journal</span>
            <span v-if="sidebarCollapsed" class="sidebar-tooltip">Journal d'activités</span>
          </Link>
        </template>
      </nav>

      <!-- Profil utilisateur en bas -->
      <div class="sidebar-user">
        <div class="sidebar-user-avatar">{{ userInitials }}</div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name">{{ $page.props.auth.user.name.split(' ')[0] }}</div>
          <div class="sidebar-user-role">{{ userRoleLabel }}</div>
        </div>
      </div>

    </aside>

    <!-- Overlay mobile -->
    <div v-if="mobileMenuOpen" class="sidebar-overlay" @click="mobileMenuOpen = false" />

    <!-- ═══════════════════════════════════
         PANNEAU PRINCIPAL (TOPBAR + CONTENT)
    ═══════════════════════════════════ -->
    <div class="main-panel">

      <!-- TOPBAR -->
      <header class="topbar">
        <!-- Gauche : hamburger mobile + logo + breadcrumb -->
        <div class="topbar-left">
          <button class="topbar-hamburger" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Menu">
            <Icon :name="mobileMenuOpen ? 'x-mark' : 'bars-3'" :size="22" />
          </button>
          
          <Link :href="route('dashboard')" class="topbar-logo">
            <img src="/images/logo.svg" alt="YA Consulting Logo" class="topbar-logo-img" />
          </Link>
          <!-- Breadcrumb -->
          <nav class="breadcrumb" aria-label="breadcrumb">
            <Link :href="route('dashboard')" class="breadcrumb-item breadcrumb-home">
              <Icon name="home" :size="16" />
            </Link>
            <span v-if="currentSection" class="breadcrumb-sep">/</span>
            <span v-if="currentSection" class="breadcrumb-item breadcrumb-current">
              {{ currentSection.label }}
            </span>
          </nav>
        </div>

        <!-- Droite : cloche + user -->
        <div class="topbar-right">
          <!-- Cloche notification -->
          <div class="topbar-bell-wrap">
            <button class="topbar-bell" aria-label="Notifications" @click.stop="toggleBellMenu">
              <Icon name="bell" :size="22" />
              <span v-if="unreadCount > 0" class="topbar-bell-badge">{{ unreadCount }}</span>
            </button>

            <transition name="dropdown">
              <div v-if="bellMenuOpen" class="navbar-bell-dropdown" @click.stop>
                <div class="navbar-bell-header">
                  <div style="display:flex; align-items:center; gap:8px;">
                    <span class="navbar-bell-title">Notifications</span>
                    <span v-if="unreadCount > 0" class="navbar-bell-count">{{ unreadCount }}</span>
                  </div>
                  <button v-if="unreadCount > 0" @click="markAllAsRead" class="navbar-bell-mark-read" title="Marquer tout comme lu">
                    <Icon name="check" :size="14" />
                    Tout marquer comme lu
                  </button>
                </div>

                <div class="navbar-bell-body">
                  <div v-if="notifications.length === 0" class="navbar-bell-empty">
                    <div class="empty-icon-wrap">
                      <Icon name="bell-slash" :size="28" class="empty-icon" />
                    </div>
                    <p>Vous êtes à jour !</p>
                    <span class="empty-sub">Aucune nouvelle notification</span>
                  </div>
                  <div v-else class="navbar-bell-list">
                    <template v-for="notification in notifications" :key="notification.id">
                      <component
                        :is="notification.link ? 'Link' : 'div'"
                        :href="notification.link"
                        class="navbar-bell-item"
                        :class="[notification.type, { 'is-unread': !notification.read }, { 'clickable': notification.link }]"
                        @click="handleNotificationClick(notification)"
                      >
                        <div class="navbar-bell-item-icon">
                          <Icon v-if="notification.type === 'danger'" name="exclamation-triangle" :size="18" />
                          <Icon v-else-if="notification.type === 'warning'" name="exclamation-circle" :size="18" />
                          <Icon v-else-if="notification.type === 'success'" name="check-circle" :size="18" />
                          <Icon v-else name="information-circle" :size="18" />
                        </div>
                        <div class="navbar-bell-item-content">
                          <div class="navbar-bell-item-title">
                            {{ notification.title }}
                            <span v-if="!notification.read" class="unread-dot"></span>
                          </div>
                          <div class="navbar-bell-item-desc">{{ notification.message }}</div>
                          <span class="navbar-bell-item-time">{{ notification.created_at }}</span>
                        </div>
                      </component>
                    </template>
                  </div>
                </div>

                <div v-if="notifications.length > 0" class="navbar-bell-footer">
                  <Link href="#" class="navbar-bell-view-all">Voir toutes les notifications</Link>
                </div>
              </div>
            </transition>
          </div>

          <!-- User dropdown -->
          <div class="topbar-user-wrap" @click.stop="toggleUserMenu">
            <div class="topbar-user">
              <div class="topbar-avatar">{{ userInitials }}</div>
              <div class="topbar-user-info">
                <div class="topbar-user-name">{{ $page.props.auth.user.name.split(' ')[0] }}</div>
                <div class="topbar-user-role">{{ userRoleLabel }}</div>
              </div>
              <Icon name="chevron-down" :size="14" class="topbar-chevron" :style="{ transform: userMenuOpen ? 'rotate(180deg)' : 'rotate(0deg)' }" />
            </div>

            <transition name="dropdown">
              <div v-if="userMenuOpen" class="navbar-dropdown">
                <Link :href="route('profile.edit')" class="navbar-dropdown-item">
                  <span class="navbar-dropdown-icon"><Icon name="user-circle" :size="20" /></span>
                  Mon profil
                </Link>
                <div class="navbar-dropdown-divider"></div>
                <Link :href="route('logout')" method="post" as="button" class="navbar-dropdown-item danger">
                  <span class="navbar-dropdown-icon"><Icon name="arrow-right-on-rectangle" :size="20" /></span>
                  Se déconnecter
                </Link>
              </div>
            </transition>
          </div>
        </div>
      </header>

      <!-- Contenu de la page -->
      <main class="app-content">
        <slot />
      </main>
    </div>

    <!-- Toast notifications -->
    <Toast />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import Toast from '@/Components/Toast.vue';

defineProps({ title: { type: String, default: 'Tableau de bord' } });

const page = usePage();

// ── Sidebar ───────────────────────────────────────────────
const sidebarCollapsed = ref(false);
const mobileMenuOpen   = ref(false);

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value;
  try { localStorage.setItem('sidebar_collapsed', sidebarCollapsed.value); } catch(e) {}
};

onMounted(() => {
  try {
    const saved = localStorage.getItem('sidebar_collapsed');
    if (saved !== null) sidebarCollapsed.value = saved === 'true';
  } catch(e) {}
  document.addEventListener('click', closeMenus);
});
onUnmounted(() => document.removeEventListener('click', closeMenus));

// ── Notifications ─────────────────────────────────────────
const getReadNotifications = () => {
  try { return JSON.parse(localStorage.getItem('read_notifications') || '[]'); } catch (e) { return []; }
};
const readNotifications = ref(getReadNotifications());

const notifications = computed(() => {
  const backendNotifs = page.props.notifications || [];
  return backendNotifs.map(n => ({ ...n, read: readNotifications.value.includes(n.id) }));
});

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length);

const markAllAsRead = () => {
  const allIds = notifications.value.map(n => n.id);
  readNotifications.value = Array.from(new Set([...readNotifications.value, ...allIds]));
  localStorage.setItem('read_notifications', JSON.stringify(readNotifications.value));
};

const handleNotificationClick = (notification) => {
  if (!notification.read) {
    readNotifications.value.push(notification.id);
    localStorage.setItem('read_notifications', JSON.stringify(readNotifications.value));
  }
  if (notification.link) bellMenuOpen.value = false;
};

// ── Menus ─────────────────────────────────────────────────
const userMenuOpen = ref(false);
const bellMenuOpen = ref(false);

const closeMenus = () => {
  userMenuOpen.value = false;
  bellMenuOpen.value = false;
};

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value;
  if (userMenuOpen.value) bellMenuOpen.value = false;
};

const toggleBellMenu = () => {
  bellMenuOpen.value = !bellMenuOpen.value;
  if (bellMenuOpen.value) userMenuOpen.value = false;
};

router.on('navigate', () => {
  mobileMenuOpen.value = false;
  userMenuOpen.value   = false;
  bellMenuOpen.value   = false;
});

// ── User ──────────────────────────────────────────────────
const userInitials = computed(() => {
  const name = page.props.auth?.user?.name || '';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const roleLabels = {
  admin:         'Administrateur',
  chef_projet:   'Chef de Projet',
  collaborateur: 'Collaborateur',
};

const userRoleLabel = computed(() => {
  const roles = page.props.auth?.user?.roles || [];
  return roleLabels[roles[0]] || 'Utilisateur';
});

// ── Breadcrumb ────────────────────────────────────────────
const sections = [
  { match: (u) => u === '/dashboard',             label: 'Tableau de bord' },
  { match: (u) => u.startsWith('/projects'),      label: 'Projets' },
  { match: (u) => u.startsWith('/expenses'),      label: 'Dépenses' },
  { match: (u) => u.startsWith('/clients'),       label: 'Clients' },
  { match: (u) => u.startsWith('/reports'),       label: 'Rapports' },
  { match: (u) => u.startsWith('/users'),         label: 'Utilisateurs' },
  { match: (u) => u.startsWith('/categories'),    label: 'Catégories' },
  { match: (u) => u.startsWith('/activity-logs'), label: "Journal d'activités" },
  { match: (u) => u.startsWith('/profile'),       label: 'Mon profil' },
];

const currentSection = computed(() => sections.find(s => s.match(page.url)));
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════
   SHELL
═══════════════════════════════════════════════════════ */
.app-shell {
  display: flex;
  min-height: 100vh;
  background: var(--color-bg);
}

/* ═══════════════════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════════════════ */
.sidebar {
  width: 240px;
  min-height: 100vh;
  background: linear-gradient(180deg, #0A1324 0%, #0F1C33 50%, #1A2B4A 100%);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  z-index: 200;
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.sidebar.collapsed {
  width: 64px;
}

/* En-tête */
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  min-height: 70px;
  flex-shrink: 0;
}

/* Le logo n'est plus dans la sidebar, on enlève le style inutile */

.sidebar-toggle {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.sidebar-toggle:hover {
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
}

/* Ligne dorée séparatrice */
.sidebar-gold-line {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  margin: 0 16px;
  opacity: 0.5;
  flex-shrink: 0;
}

/* Navigation */
.sidebar-nav {
  flex: 1;
  padding: 16px 8px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow-y: auto;
  overflow-x: hidden;
}

.sidebar-section-label {
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 1px;
  color: rgba(255,255,255,0.5);
  padding: 8px 10px 4px;
  white-space: nowrap;
  overflow: hidden;
  transition: opacity 0.2s;
}

.sidebar.collapsed .sidebar-section-label {
  opacity: 0;
  height: 0;
  padding: 0;
}

.sidebar-divider {
  height: 1px;
  background: rgba(255,255,255,0.05);
  margin: 10px 8px;
  flex-shrink: 0;
}

/* Items de navigation */
.sidebar-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 10px;
  border-radius: 10px;
  color: rgba(255,255,255,0.7);
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s ease;
  position: relative;
  white-space: nowrap;
  overflow: visible;
}

.sidebar-item:hover {
  color: #fff;
  background: rgba(255,255,255,0.05);
}

/* État actif : pill sombre sur fond doré */
.sidebar-item.active {
  background: linear-gradient(135deg, #D4B154, #EBD08C);
  color: #0F1C33;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(212, 177, 84, 0.3);
}

.sidebar-item-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 22px;
}

.sidebar-item-label {
  font-size: 0.875rem;
  font-weight: 500;
  transition: opacity 0.2s ease;
  overflow: hidden;
}

.sidebar.collapsed .sidebar-item-label {
  opacity: 0;
  width: 0;
}

/* Tooltip au survol en mode réduit */
.sidebar-tooltip {
  display: none;
  position: absolute;
  left: calc(100% + 12px);
  top: 50%;
  transform: translateY(-50%);
  background: #fff;
  color: #0F1C33;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 6px;
  white-space: nowrap;
  pointer-events: none;
  z-index: 300;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.sidebar-tooltip::before {
  content: '';
  position: absolute;
  right: 100%;
  top: 50%;
  transform: translateY(-50%);
  border: 5px solid transparent;
  border-right-color: #fff;
}

.sidebar.collapsed .sidebar-item:hover .sidebar-tooltip {
  display: block;
}

/* Profil utilisateur sidebar */
.sidebar-user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px;
  border-top: 1px solid rgba(255,255,255,0.05);
  flex-shrink: 0;
  overflow: hidden;
}

.sidebar-user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #D4B154, #EBD08C);
  color: #0F1C33;
  font-weight: 700;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 2px solid rgba(255,255,255,0.1);
}

.sidebar-user-info {
  transition: opacity 0.2s ease;
  overflow: hidden;
}

.sidebar.collapsed .sidebar-user-info {
  opacity: 0;
  width: 0;
}

.sidebar-user-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: #fff;
  white-space: nowrap;
}

.sidebar-user-role {
  font-size: 0.72rem;
  color: rgba(255,255,255,0.5);
  font-weight: 500;
  white-space: nowrap;
}

/* Overlay mobile */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  z-index: 199;
  backdrop-filter: blur(2px);
}

/* ═══════════════════════════════════════════════════════
   PANNEAU PRINCIPAL
═══════════════════════════════════════════════════════ */
.main-panel {
  width: calc(100% - 240px);
  margin-left: 240px;
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.app-shell.sidebar-collapsed .main-panel {
  margin-left: 64px;
  width: calc(100% - 64px);
}

/* ═══════════════════════════════════════════════════════
   TOPBAR
═══════════════════════════════════════════════════════ */
.topbar {
  height: 64px;
  width: 100%;
  box-sizing: border-box;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(235, 208, 140, 0.35);
  box-shadow: 0 2px 12px rgba(15, 28, 51, 0.04);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  position: sticky;
  top: 0;
  z-index: 100;
}

.topbar-left, .topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.topbar-logo {
  display: flex;
  align-items: center;
  margin-right: 8px;
}

.topbar-logo-img {
  height: 36px;
  width: auto;
  max-width: 100%;
  object-fit: contain;
}

/* Hamburger mobile */
.topbar-hamburger {
  display: none;
  background: none;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 6px;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.topbar-hamburger:hover {
  background: var(--color-bg-light);
  color: var(--color-primary);
}

/* Breadcrumb */
.breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
}

.breadcrumb-item { color: var(--color-text-muted); text-decoration: none; }
.breadcrumb-home:hover { color: var(--color-primary); }
.breadcrumb-sep { color: var(--color-border); }
.breadcrumb-current { color: var(--color-text); font-weight: 500; }

/* Cloche */
.topbar-bell-wrap { position: relative; }

.topbar-bell {
  background: none;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  color: var(--color-text-muted);
  cursor: pointer;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: all 0.2s;
}

.topbar-bell:hover {
  color: var(--color-primary);
  border-color: var(--color-accent);
  background: rgba(212,177,84,0.06);
}

.topbar-bell-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: linear-gradient(135deg, #D4B154, #B8963E);
  color: #0F1C33;
  font-size: 0.6rem;
  font-weight: 700;
  padding: 2px 5px;
  border-radius: 10px;
  min-width: 18px;
  text-align: center;
}

/* User topbar */
.topbar-user-wrap { position: relative; cursor: pointer; }

.topbar-user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 12px;
  border-radius: 10px;
  border: 1px solid var(--color-border);
  transition: all 0.2s;
}

.topbar-user:hover {
  background: rgba(212,177,84,0.06);
  border-color: var(--color-accent);
}

.topbar-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0F1C33, #1A2B4A);
  color: #D4B154;
  font-weight: 700;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid rgba(212,177,84,0.4);
}

.topbar-user-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
}

.topbar-user-role {
  font-size: 0.72rem;
  color: var(--color-text-muted);
}

.topbar-chevron {
  color: var(--color-text-muted);
  transition: transform 0.2s;
}

/* ═══════════════════════════════════════════════════════
   CONTENT
═══════════════════════════════════════════════════════ */
.app-content {
  flex: 1;
  padding: var(--space-xl);
}

/* ═══════════════════════════════════════════════════════
   DROPDOWN NOTIFICATIONS (repris de l'existant)
═══════════════════════════════════════════════════════ */
.navbar-bell-dropdown {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  width: 360px;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: 0 16px 40px rgba(15, 28, 51, 0.12);
  z-index: 400;
  overflow: hidden;
}

.navbar-bell-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid var(--color-border);
}

.navbar-bell-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-text);
}

.navbar-bell-count {
  background: linear-gradient(135deg, #D4B154, #EBD08C);
  color: #0F1C33;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 10px;
}

.navbar-bell-mark-read {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.75rem;
  color: var(--color-text-muted);
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s;
}
.navbar-bell-mark-read:hover { background: var(--color-bg-light); color: var(--color-primary); }

.navbar-bell-body { max-height: 320px; overflow-y: auto; }

.navbar-bell-empty {
  text-align: center;
  padding: 32px 16px;
  color: var(--color-text-muted);
  font-size: 0.875rem;
}
.empty-icon-wrap { margin-bottom: 8px; }
.empty-icon { color: var(--color-border); }
.empty-sub { font-size: 0.78rem; color: var(--color-text-light); margin-top: 4px; display: block; }

.navbar-bell-list { display: flex; flex-direction: column; }

.navbar-bell-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--color-bg-light);
  text-decoration: none;
  transition: background 0.15s;
}
.navbar-bell-item.clickable { cursor: pointer; }
.navbar-bell-item.clickable:hover { background: var(--color-bg-light); }
.navbar-bell-item.is-unread { background: rgba(212,177,84,0.04); }

.navbar-bell-item-icon {
  width: 32px; height: 32px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.navbar-bell-item.danger .navbar-bell-item-icon { background: rgba(220,38,38,0.1); color: var(--color-danger); }
.navbar-bell-item.warning .navbar-bell-item-icon { background: rgba(245,158,11,0.1); color: var(--color-warning); }
.navbar-bell-item.success .navbar-bell-item-icon { background: rgba(21,128,61,0.1); color: var(--color-success); }
.navbar-bell-item.info .navbar-bell-item-icon { background: rgba(2,132,199,0.08); color: var(--color-info); }

.navbar-bell-item-content { flex: 1; min-width: 0; }
.navbar-bell-item-title {
  font-size: 0.8rem; font-weight: 600; color: var(--color-text);
  display: flex; align-items: center; gap: 6px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.unread-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #D4B154; flex-shrink: 0;
}
.navbar-bell-item-desc { font-size: 0.75rem; color: var(--color-text-muted); line-height: 1.4; }
.navbar-bell-item-time { font-size: 0.68rem; color: var(--color-text-light); margin-top: 3px; display: block; }

.navbar-bell-footer {
  padding: 10px 16px;
  border-top: 1px solid var(--color-border);
  text-align: center;
}
.navbar-bell-view-all {
  font-size: 0.8rem;
  color: var(--color-accent-dark);
  font-weight: 600;
  text-decoration: none;
}

/* ═══════════════════════════════════════════════════════
   DROPDOWN USER
═══════════════════════════════════════════════════════ */
.navbar-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 180px;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: 0 10px 30px rgba(15, 28, 51, 0.10);
  overflow: hidden;
  z-index: 400;
}

.navbar-dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  text-decoration: none;
  font-size: 0.875rem;
  color: var(--color-text);
  transition: background 0.15s;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
}
.navbar-dropdown-item:hover { background: var(--color-bg-light); }
.navbar-dropdown-item.danger { color: var(--color-danger); }
.navbar-dropdown-item.danger:hover { background: rgba(220,38,38,0.06); }
.navbar-dropdown-icon { display: flex; align-items: center; color: var(--color-text-muted); }
.navbar-dropdown-divider { height: 1px; background: var(--color-border); }

/* ═══════════════════════════════════════════════════════
   TRANSITIONS
═══════════════════════════════════════════════════════ */
.dropdown-enter-active, .dropdown-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-enter-from, .dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}

/* ═══════════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .sidebar {
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s;
  }

  /* Le sidebar s'affiche sur mobile si mobileMenuOpen */
  .app-shell.mobile-open .sidebar {
    transform: translateX(0);
    width: 240px !important;
  }

  .main-panel {
    margin-left: 0 !important;
    width: 100% !important;
  }

  .topbar-hamburger {
    display: flex;
  }

  .topbar-user-name, .topbar-user-role {
    display: none;
  }
}

@media (max-width: 480px) {
  .navbar-bell-dropdown {
    position: fixed;
    top: 72px;
    left: 12px;
    right: 12px;
    width: auto;
  }
  .app-content {
    padding: var(--space-md);
  }
}
</style>
