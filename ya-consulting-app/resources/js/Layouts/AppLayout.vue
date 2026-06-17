<template>
  <div class="app-shell">
    <!-- Sidebar -->
    <aside class="sidebar" :class="{ open: sidebarOpen }">
      <!-- Logo -->
      <div class="sidebar-logo">
        <div class="sidebar-logo-text">
          <span>YA</span> Consulting
        </div>
        <p style="color: rgba(255,255,255,.4); font-size: .7rem; margin: 4px 0 0; font-weight: 500;">
          Gestion de Projets
        </p>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <span class="sidebar-section">Principal</span>

        <Link :href="route('dashboard')" class="sidebar-link" :class="{ active: $page.url === '/dashboard' }">
          <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
          Tableau de bord
        </Link>

        <span class="sidebar-section">Projets</span>

        <Link :href="route('projects.index')" class="sidebar-link" :class="{ active: $page.url.startsWith('/projects') }">
          <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          Projets
        </Link>

        <Link :href="route('expenses.index')" class="sidebar-link" :class="{ active: $page.url.startsWith('/expenses') }">
          <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Dépenses
        </Link>

        <Link :href="route('clients.index')" class="sidebar-link" :class="{ active: $page.url.startsWith('/clients') }">
          <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          Clients
        </Link>

        <span class="sidebar-section">Rapports</span>

        <Link :href="route('reports.index')" class="sidebar-link" :class="{ active: $page.url.startsWith('/reports') }">
          <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Rapports
        </Link>

        <!-- Admin uniquement -->
        <template v-if="$page.props.auth.user.roles?.includes('admin')">
          <span class="sidebar-section">Administration</span>
          <Link :href="route('users.index')" class="sidebar-link" :class="{ active: $page.url.startsWith('/users') }">
            <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Utilisateurs
          </Link>
        </template>
      </nav>

      <!-- User Footer -->
      <div class="sidebar-footer">
        <div style="display:flex; align-items:center; gap:10px;">
          <Link :href="route('profile.edit')" style="width:36px; height:36px; border-radius:50%; background:var(--color-accent); display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--color-primary-dark); font-size:.875rem; text-decoration:none;">
            {{ userInitials }}
          </Link>
          <div style="flex:1; min-width:0;">
            <Link :href="route('profile.edit')" style="color:#fff; font-size:.825rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; text-decoration:none; display:block;">
              {{ $page.props.auth.user.name }}
            </Link>
            <div style="color:rgba(255,255,255,.4); font-size:.7rem;">
              {{ userRoleLabel }}
            </div>
          </div>
          <Link :href="route('logout')" method="post" as="button"
            style="background:none; border:none; cursor:pointer; color:rgba(255,255,255,.4); padding:4px;">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
          </Link>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="app-main">
      <!-- Topbar -->
      <header class="topbar">
        <div style="display:flex; align-items:center; gap:var(--space-md);">
          <!-- Mobile toggle -->
          <button class="btn btn-outline btn-sm" style="display:none" @click="sidebarOpen = !sidebarOpen" id="sidebar-toggle">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <span class="topbar-title">{{ title }}</span>
        </div>

        <div class="topbar-actions">
          <!-- Flash message -->
          <transition name="fade">
            <div v-if="flashSuccess" class="alert alert-success" style="margin:0; padding: 6px 14px;">
              ✓ {{ flashSuccess }}
            </div>
          </transition>
        </div>
      </header>

      <!-- Page Content -->
      <div class="app-content">
        <!-- Flash alert -->
        <transition name="slide-fade">
          <div v-if="flashSuccess" class="alert alert-success animate-fade-up">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ flashSuccess }}
          </div>
        </transition>

        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
  title: { type: String, default: 'Tableau de bord' },
});

const page = usePage();
const sidebarOpen = ref(false);

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
</script>
