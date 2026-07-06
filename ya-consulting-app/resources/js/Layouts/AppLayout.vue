<template>
  <div class="app-shell">
    <!-- ═══════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════ -->
    <main class="app-main">
      <!-- ═══════════════════════════════════
           NAVBAR
      ═══════════════════════════════════ -->
      <header class="navbar">

        <!-- Gauche : toggle mobile + logo + breadcrumb -->
        <div class="navbar-left">
          <button
            class="navbar-toggle"
            @click="mobileMenuOpen = !mobileMenuOpen"
            aria-label="Afficher le menu"
          >
            <Icon :name="mobileMenuOpen ? 'x-mark' : 'bars-3'" :size="24" />
          </button>

          <!-- Logo -->
          <Link :href="route('dashboard')" class="navbar-logo-link">
            <img src="/images/logo.svg" alt="YA Consulting Logo" class="navbar-logo-img" />
          </Link>

          <span class="navbar-logo-sep">|</span>

          <!-- Breadcrumb -->
          <nav class="breadcrumb" aria-label="breadcrumb">
            <Link :href="route('dashboard')" class="breadcrumb-item breadcrumb-home">
              <Icon name="home" :size="18" />
            </Link>
            <span v-if="currentSection" class="breadcrumb-sep">/</span>
            <span v-if="currentSection" class="breadcrumb-item breadcrumb-current">
              {{ currentSection.label }}
            </span>
          </nav>
        </div>

        <!-- Centre : liens rapides -->
        <nav class="navbar-nav">
          <Link :href="route('dashboard')" class="navbar-link" :class="{ active: $page.url === '/dashboard' }">
            <Icon name="chart-pie" :size="22" />
            Tableau de bord
          </Link>
          <Link :href="route('projects.index')" class="navbar-link" :class="{ active: $page.url.startsWith('/projects') }">
            <Icon name="briefcase" :size="22" />
            Projets
          </Link>
          <Link :href="route('expenses.index')" class="navbar-link" :class="{ active: $page.url.startsWith('/expenses') }">
            <Icon name="banknotes" :size="22" />
            Dépenses
          </Link>
          <Link :href="route('clients.index')" class="navbar-link" :class="{ active: $page.url.startsWith('/clients') }">
            <Icon name="users" :size="22" />
            Clients
          </Link>
          <template v-if="!$page.props.auth.user.roles?.includes('collaborateur')">
            <Link :href="route('reports.index')" class="navbar-link" :class="{ active: $page.url.startsWith('/reports') }">
              <Icon name="chart-bar" :size="22" />
              Rapports
            </Link>
          </template>

          <!-- Admin uniquement -->
          <template v-if="$page.props.auth.user.roles?.includes('admin')">
            <Link :href="route('users.index')" class="navbar-link" :class="{ active: $page.url.startsWith('/users') }">
              <Icon name="user-group" :size="22" />
              Utilisateurs
            </Link>
            <Link :href="route('categories.index')" class="navbar-link" :class="{ active: $page.url.startsWith('/categories') }">
              <Icon name="tag" :size="22" />
              Catégories
            </Link>
            <Link :href="route('activity-logs.index')" class="navbar-link" :class="{ active: $page.url.startsWith('/activity-logs') }">
              <Icon name="clipboard-document-list" :size="22" />
              Journal
            </Link>
          </template>
        </nav>

        <!-- Droite : flash + cloche + user -->
        <div class="navbar-right">

          <!-- Flash -->
          <transition name="fade">
            <div v-if="flashSuccess" class="alert alert-success navbar-flash" style="margin:0; padding:5px 12px; font-size:.8rem;">
              ✓ {{ flashSuccess }}
            </div>
          </transition>

          <!-- Cloche notification -->
          <div class="navbar-bell-wrap">
            <button class="navbar-bell" aria-label="Notifications" @click.stop="toggleBellMenu">
              <Icon name="bell" :size="24" />
              <span v-if="unreadCount > 0" class="navbar-bell-badge">
                {{ unreadCount }}
              </span>
            </button>

            <!-- Notification Dropdown -->
            <transition name="dropdown">
              <div v-if="bellMenuOpen" class="navbar-bell-dropdown" @click.stop>
                <div class="navbar-bell-header">
                  <div style="display:flex; align-items:center; gap:8px;">
                    <span class="navbar-bell-title">Notifications</span>
                    <span v-if="unreadCount > 0" class="navbar-bell-count">
                      {{ unreadCount }}
                    </span>
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
          <div class="navbar-user-wrap" @click.stop="toggleUserMenu">
            <div class="navbar-user">
              <div class="navbar-avatar">{{ userInitials }}</div>
              <div class="navbar-user-info">
                <div class="navbar-user-name">{{ $page.props.auth.user.name.split(' ')[0] }}</div>
                <div class="navbar-user-role">{{ userRoleLabel }}</div>
              </div>
              <Icon name="chevron-down" :size="16" style="color:var(--color-text-muted); transition:transform .2s;" :style="{ transform: userMenuOpen ? 'rotate(180deg)' : 'rotate(0deg)' }" />
            </div>

            <!-- Dropdown menu -->
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

      <!-- Mobile Navigation Menu -->
      <transition name="mobile-menu">
        <div v-if="mobileMenuOpen" class="navbar-mobile-menu">
          <Link :href="route('dashboard')" class="navbar-mobile-link" :class="{ active: $page.url === '/dashboard' }">
            <Icon name="chart-pie" :size="24" />
            Tableau de bord
          </Link>
          <Link :href="route('projects.index')" class="navbar-mobile-link" :class="{ active: $page.url.startsWith('/projects') }">
            <Icon name="briefcase" :size="24" />
            Projets
          </Link>
          <Link :href="route('expenses.index')" class="navbar-mobile-link" :class="{ active: $page.url.startsWith('/expenses') }">
            <Icon name="banknotes" :size="20" />
            Dépenses
          </Link>
          <Link :href="route('clients.index')" class="navbar-mobile-link" :class="{ active: $page.url.startsWith('/clients') }">
            <Icon name="users" :size="20" />
            Clients
          </Link>
          <template v-if="!$page.props.auth.user.roles?.includes('collaborateur')">
            <Link :href="route('reports.index')" class="navbar-mobile-link" :class="{ active: $page.url.startsWith('/reports') }">
              <Icon name="chart-bar" :size="20" />
              Rapports
            </Link>
          </template>
          <template v-if="$page.props.auth.user.roles?.includes('admin')">
            <div class="navbar-mobile-divider"></div>
            <span class="navbar-mobile-section">Administration</span>
            <Link :href="route('users.index')" class="navbar-mobile-link" :class="{ active: $page.url.startsWith('/users') }">
              <Icon name="user-group" :size="20" />
              Utilisateurs
            </Link>
            <Link :href="route('categories.index')" class="navbar-mobile-link" :class="{ active: $page.url.startsWith('/categories') }">
              <Icon name="tag" :size="20" />
              Catégories
            </Link>
            <Link :href="route('activity-logs.index')" class="navbar-mobile-link" :class="{ active: $page.url.startsWith('/activity-logs') }">
              <Icon name="clipboard-document-list" :size="20" />
              Journal
            </Link>
          </template>
        </div>
      </transition>

      <!-- Page Content -->
      <div class="app-content">
        <!-- Flash alert -->
        <transition name="slide-fade">
          <div v-if="flashSuccess" class="alert alert-success animate-fade-up">
            <Icon name="check-circle" :size="18" style="color: var(--color-success);" />
            {{ flashSuccess }}
          </div>
        </transition>

        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  title: { type: String, default: 'Tableau de bord' },
});

const page = usePage();

// Récupération des IDs des notifications déjà lues depuis le cache local
const getReadNotifications = () => {
  try {
    return JSON.parse(localStorage.getItem('read_notifications') || '[]');
  } catch (e) {
    return [];
  }
};

const readNotifications = ref(getReadNotifications());

// Notifications réelles provenant du backend (via Inertia)
const notifications = computed(() => {
  const backendNotifs = page.props.notifications || [];
  return backendNotifs.map(n => ({
    ...n,
    read: readNotifications.value.includes(n.id)
  }));
});

// Computed
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
  if (notification.link) {
    bellMenuOpen.value = false;
  }
};

const mobileMenuOpen = ref(false);
const userMenuOpen   = ref(false);
const bellMenuOpen   = ref(false);


// Fermer dropdown au clic extérieur
const closeMenus = () => {
  userMenuOpen.value = false;
  bellMenuOpen.value = false;
};
onMounted(()  => document.addEventListener('click', closeMenus));
onUnmounted(() => document.removeEventListener('click', closeMenus));

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value;
  if (userMenuOpen.value) {
    bellMenuOpen.value = false;
  }
};

const toggleBellMenu = () => {
  bellMenuOpen.value = !bellMenuOpen.value;
  if (bellMenuOpen.value) {
    userMenuOpen.value = false;
  }
};

router.on('navigate', () => {
  mobileMenuOpen.value = false;
  userMenuOpen.value   = false;
  bellMenuOpen.value   = false;
});

const flashSuccess = computed(() => page.props.flash?.success);

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

// Breadcrumb sections
const sections = [
  { match: (u) => u === '/dashboard',          label: 'Tableau de bord' },
  { match: (u) => u.startsWith('/projects'),   label: 'Projets' },
  { match: (u) => u.startsWith('/expenses'),   label: 'Dépenses' },
  { match: (u) => u.startsWith('/clients'),    label: 'Clients' },
  { match: (u) => u.startsWith('/reports'),    label: 'Rapports' },
  { match: (u) => u.startsWith('/users'),      label: 'Utilisateurs' },
  { match: (u) => u.startsWith('/categories'), label: 'Catégories' },
  { match: (u) => u.startsWith('/activity-logs'), label: "Journal d'activités" },
  { match: (u) => u.startsWith('/profile'),    label: 'Mon profil' },
];

const currentSection = computed(() =>
  sections.find(s => s.match(page.url))
);
</script>

<style scoped>
/* ═══════════════════════════════════
   NAVBAR (Premium Glassmorphism)
═══════════════════════════════════ */
.navbar {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  height: 80px;
  padding: 0 var(--space-xl);
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 4px 20px -2px rgba(0,0,0,.03);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
}

/* Gauche */
.navbar-left {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  flex-shrink: 0;
}

.navbar-toggle {
  display: none;
  width: 36px; height: 36px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: transparent;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  color: var(--color-text-muted);
  transition: all .2s ease;
}

.navbar-toggle:hover {
  background: rgba(26, 43, 74, 0.04);
  color: var(--color-primary);
  transform: scale(1.05);
}

/* Logo */
.navbar-logo-link {
  display: flex;
  align-items: center;
  height: 42px;
  transition: transform .2s ease;
}

.navbar-logo-link:hover {
  transform: scale(1.02);
}

.navbar-logo-img {
  height: 100%;
  object-fit: contain;
}

.navbar-logo-sep {
  color: var(--color-border);
  font-size: 1.25rem;
  font-weight: 300;
  user-select: none;
  margin: 0 2px;
}

/* Breadcrumb */
.breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
}

.breadcrumb-item {
  font-size: 1rem;
  font-weight: 500;
  color: var(--color-text-muted);
  text-decoration: none;
  transition: color .15s ease;
}

.breadcrumb-home {
  display: flex;
  align-items: center;
  color: var(--color-primary);
}

.breadcrumb-home:hover { color: var(--color-accent-dark); }

.breadcrumb-sep {
  color: var(--color-border);
  font-size: .85rem;
  user-select: none;
}

.breadcrumb-current {
  color: var(--color-text);
  font-weight: 600;
  font-size: 1rem;
}

/* Centre — liens rapides */
.navbar-nav {
  display: flex;
  align-items: center;
  gap: 4px;
  flex: 1;
  justify-content: flex-start;
  overflow-x: auto;
  -ms-overflow-style: none;
  scrollbar-width: none;
  min-width: 0;
  padding: 0 10px;
}

.navbar-nav::-webkit-scrollbar {
  display: none;
}

.navbar-link {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 20px;
  font-size: 1.05rem;
  font-weight: 500;
  color: var(--color-text-muted);
  text-decoration: none;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  position: relative;
  flex-shrink: 0;
}

.navbar-link:hover {
  background: rgba(26, 43, 74, 0.04);
  color: var(--color-primary);
  transform: translateY(-1px);
}

.navbar-link.active {
  background: rgba(26, 43, 74, 0.06);
  color: var(--color-primary);
  font-weight: 600;
  box-shadow: inset 0 0 0 1px rgba(26, 43, 74, 0.04);
}

.navbar-link.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 14px;
  height: 3px;
  background: var(--color-accent);
  border-radius: 3px 3px 0 0;
}

/* Droite */
.navbar-right {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  flex-shrink: 0;
  margin-left: auto;
}

/* Cloche avec animation de balancement */
.navbar-bell {
  position: relative;
  width: 36px; height: 36px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: transparent;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: var(--color-text-muted);
  transition: background .15s ease, color .15s ease;
}

.navbar-bell:hover {
  background: var(--color-bg-light);
  color: var(--color-primary);
}

@keyframes bell-swing {
  0%, 100% { transform: rotate(0deg); }
  20% { transform: rotate(15deg); }
  40% { transform: rotate(-10deg); }
  60% { transform: rotate(5deg); }
  80% { transform: rotate(-5deg); }
}

.navbar-bell:hover svg,
.navbar-bell:hover .icon {
  animation: bell-swing 0.6s ease;
}

.navbar-bell-dot {
  position: absolute;
  top: 6px; right: 6px;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--color-accent);
  border: 1.5px solid #fff;
  animation: pulse-accent 2s infinite;
}

/* User dropdown wrapper */
.navbar-user-wrap {
  position: relative;
  cursor: pointer;
}

.navbar-user {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 5px 10px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  transition: all .2s ease;
  user-select: none;
}

.navbar-user:hover {
  background: var(--color-bg-light);
  border-color: var(--color-primary);
}

.navbar-avatar {
  width: 30px; height: 30px;
  border-radius: 50%;
  background: var(--color-primary);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700;
  font-size: .72rem;
  flex-shrink: 0;
  box-shadow: 0 2px 4px rgba(26, 43, 74, 0.1);
}

.navbar-user-info { line-height: 1.2; }

.navbar-user-name {
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--color-text);
}

.navbar-user-role {
  font-size: .9rem;
  color: var(--color-text-muted);
}

/* Dropdown menu (Glassmorphism & subtle scale) */
.navbar-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 190px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(226, 232, 240, 0.7);
  border-radius: var(--radius-md);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 16px -6px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  z-index: 200;
  padding: 5px;
}

.navbar-dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  font-size: .825rem;
  font-weight: 500;
  color: var(--color-text);
  text-decoration: none;
  transition: all .2s ease;
  background: none;
  border: none;
  width: 100%;
  cursor: pointer;
  text-align: left;
  border-radius: var(--radius-sm);
}

.navbar-dropdown-item:hover {
  background: rgba(26, 43, 74, 0.04);
  color: var(--color-primary);
}

.navbar-dropdown-item.danger {
  color: var(--color-danger);
}

.navbar-dropdown-item.danger:hover {
  background: rgba(239, 68, 68, 0.08);
}

.navbar-dropdown-icon {
  width: 32px; height: 32px;
  border-radius: 6px;
  background: var(--color-bg-light);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.navbar-dropdown-item.danger .navbar-dropdown-icon {
  background: rgba(239, 68, 68, 0.08);
  color: var(--color-danger);
}

.navbar-dropdown-divider {
  height: 1px;
  background: var(--color-border);
  margin: 4px 0;
}

/* Mobile Menu Dropdown (Glassmorphic) */
.navbar-mobile-menu {
  position: absolute;
  top: 80px;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  padding: var(--space-md);
  z-index: 99;
  gap: 4px;
}

.navbar-mobile-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  border-radius: 20px;
  color: var(--color-text-muted);
  text-decoration: none;
  font-size: .9rem;
  font-weight: 500;
  transition: all .2s ease;
}

.navbar-mobile-link:hover, .navbar-mobile-link.active {
  background: rgba(26, 43, 74, 0.05);
  color: var(--color-primary);
  font-weight: 600;
}

.navbar-mobile-divider {
  height: 1px;
  background: var(--color-border);
  margin: var(--space-sm) 0;
}

.navbar-mobile-section {
  display: block;
  padding: var(--space-xs) 14px;
  font-size: .65rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--color-text-muted);
  opacity: .7;
}

/* Dropdown transition (Subtle scale & fade) */
.dropdown-enter-active, .dropdown-leave-active { transition: all .2s cubic-bezier(0.16, 1, 0.3, 1); }
.dropdown-enter-from { opacity: 0; transform: translateY(-8px) scale(0.98); }
.dropdown-leave-to   { opacity: 0; transform: translateY(-8px) scale(0.98); }

/* Mobile menu transition */
.mobile-menu-enter-active, .mobile-menu-leave-active { transition: all .25s ease-in-out; }
.mobile-menu-enter-from, .mobile-menu-leave-to { opacity: 0; transform: translateY(-10px); }

/* Bell dropdown wrapper */
.navbar-bell-wrap {
  position: relative;
}

/* Badge for bell instead of dot */
.navbar-bell-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: var(--color-danger);
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  min-width: 16px;
  height: 16px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 1.5px solid #fff;
  box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
}

.navbar-bell-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 320px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: var(--radius-md);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 16px -6px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  z-index: 200;
  display: flex;
  flex-direction: column;
}

.navbar-bell-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-md);
  border-bottom: 1px solid var(--color-border);
}

.navbar-bell-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text);
}

.navbar-bell-count {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--color-danger);
  background: rgba(239, 68, 68, 0.08);
  padding: 2px 6px;
  border-radius: 10px;
}

.navbar-bell-mark-read {
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: var(--radius-sm);
  transition: all 0.2s ease;
}

.navbar-bell-mark-read:hover {
  background: rgba(26, 43, 74, 0.05);
}

.navbar-bell-body {
  max-height: 350px;
  overflow-y: auto;
}

/* Custom scrollbar for notifications list */
.navbar-bell-body::-webkit-scrollbar { width: 4px; }
.navbar-bell-body::-webkit-scrollbar-track { background: transparent; }
.navbar-bell-body::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: 2px; }

.navbar-bell-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  color: var(--color-text-muted);
  text-align: center;
}

.empty-icon-wrap {
  width: 56px; height: 56px;
  border-radius: 50%;
  background: rgba(226, 232, 240, 0.4);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 12px;
  color: var(--color-border);
}

.navbar-bell-empty p {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 4px 0;
}

.navbar-bell-empty .empty-sub {
  font-size: 0.75rem;
}

.navbar-bell-list { display: flex; flex-direction: column; }

.navbar-bell-item {
  display: flex;
  gap: var(--space-sm);
  padding: 14px var(--space-md);
  border-bottom: 1px solid rgba(226, 232, 240, 0.5);
  text-decoration: none;
  transition: background 0.2s ease;
  position: relative;
}

.navbar-bell-item:last-child { border-bottom: none; }
.navbar-bell-item:hover { background: rgba(26, 43, 74, 0.02); }
.navbar-bell-item.clickable { cursor: pointer; }

.navbar-bell-item.is-unread {
  background: rgba(43, 108, 176, 0.03);
}

.navbar-bell-item-icon {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.navbar-bell-item.info .navbar-bell-item-icon { background: rgba(43, 108, 176, 0.1); color: var(--color-info); }
.navbar-bell-item.success .navbar-bell-item-icon { background: rgba(16, 185, 129, 0.1); color: var(--color-success); }
.navbar-bell-item.warning .navbar-bell-item-icon { background: rgba(245, 158, 11, 0.1); color: var(--color-warning); }
.navbar-bell-item.danger .navbar-bell-item-icon { background: rgba(239, 68, 68, 0.1); color: var(--color-danger); }

.navbar-bell-item-content { flex: 1; min-width: 0; }

.navbar-bell-item-title {
  font-size: 0.825rem;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 3px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.unread-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--color-primary);
  display: inline-block;
  box-shadow: 0 0 0 2px rgba(43, 108, 176, 0.2);
}

.navbar-bell-item-desc {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  line-height: 1.4;
  margin-bottom: 5px;
}

.navbar-bell-item-time {
  font-size: 0.65rem;
  color: #94a3b8;
  font-weight: 500;
}

.navbar-bell-footer {
  padding: 10px;
  text-align: center;
  border-top: 1px solid var(--color-border);
  background: rgba(248, 250, 252, 0.5);
}

.navbar-bell-view-all {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-primary);
  text-decoration: none;
}

.navbar-bell-view-all:hover {
  text-decoration: underline;
}

.navbar-bell-item-icon {
  width: 28px;
  height: 28px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Different alert styles */
.navbar-bell-item.danger {
  border-left: 3px solid var(--color-danger);
}
.navbar-bell-item.danger .navbar-bell-item-icon {
  background: rgba(239, 68, 68, 0.08);
  color: var(--color-danger);
}

.navbar-bell-item.warning {
  border-left: 3px solid var(--color-warning);
}
.navbar-bell-item.warning .navbar-bell-item-icon {
  background: rgba(245, 158, 11, 0.08);
  color: var(--color-warning);
}

.navbar-bell-item.info {
  border-left: 3px solid var(--color-primary);
}
.navbar-bell-item.info .navbar-bell-item-icon {
  background: rgba(26, 43, 74, 0.06);
  color: var(--color-primary);
}

.navbar-bell-item-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  min-width: 0; /* truncate text if needed */
}

.navbar-bell-item-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.navbar-bell-item-desc {
  font-size: 0.7rem;
  color: var(--color-text-muted);
  line-height: 1.3;
}

.navbar-bell-item-time {
  font-size: 0.65rem;
  color: var(--color-text-muted);
  margin-top: 2px;
  align-self: flex-end;
}

/* Responsive */
@media (max-width: 1400px) {
  .navbar-nav { display: none; }
  .navbar-toggle { display: flex; }
}

@media (max-width: 600px) {
  .navbar-user-info { display: none; }
  .navbar-user { gap: 4px; padding: 5px 6px; }
  .breadcrumb { display: none; }
  .navbar-logo-sep { display: none; }
  .navbar-flash { display: none; }
}

@media (max-width: 480px) {
  .navbar-bell-dropdown {
    position: fixed;
    top: 58px;
    left: 12px;
    right: 12px;
    width: auto;
  }
}
</style>
