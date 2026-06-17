<template>
  <AppLayout title="Utilisateurs">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <h1>Gestion des Utilisateurs</h1>
          <p>{{ users.total }} utilisateur{{ users.total > 1 ? 's' : '' }} enregistré{{ users.total > 1 ? 's' : '' }}</p>
        </div>
      </div>

      <!-- Erreurs de suppression -->
      <div v-if="$page.props.errors.delete" class="alert alert-danger mb-lg">
        {{ $page.props.errors.delete }}
      </div>

      <!-- Filtres -->
      <div class="filters-bar">
        <input
          v-model="search"
          class="form-control"
          placeholder="🔍 Rechercher un utilisateur..."
          @input="applyFilters"
        />
        <button v-if="search" class="btn btn-outline btn-sm" @click="clearFilters">
          ✕ Effacer
        </button>
      </div>

      <!-- Tableau -->
      <div class="card">
        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Inscrit le</th>
                <th style="width:120px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users.data" :key="user.id">
                <td><strong style="color:var(--color-primary)">{{ user.name }}</strong></td>
                <td>{{ user.email }}</td>
                <td>
                  <span class="badge" :class="getRoleBadgeClass(user.role)">
                    {{ getRoleLabel(user.role) }}
                  </span>
                </td>
                <td style="color:var(--color-text-muted); font-size:.8rem;">{{ user.created_at }}</td>
                <td style="text-align:right">
                  <div style="display:flex; gap:6px; justify-content:flex-end;">
                    <button @click="openEditModal(user)" class="btn btn-outline btn-sm" style="padding:4px 8px;">
                      ✏️
                    </button>
                    <button v-if="user.id !== $page.props.auth.user.id" @click="deleteUser(user.id)" class="btn btn-outline btn-sm text-danger" style="padding:4px 8px; border-color:var(--color-border)">
                      ✕
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!users.data.length">
                <td colspan="5" style="text-align:center; padding:40px; color:var(--color-text-muted);">
                  Aucun utilisateur trouvé
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1"
          style="display:flex; justify-content:center; gap:6px; padding:16px; border-top:1px solid var(--color-border);">
          <Link v-for="link in users.links" :key="link.label"
            :href="link.url || '#'"
            class="btn btn-outline btn-sm"
            :style="link.active ? 'background:var(--color-primary); color:#fff; border-color:var(--color-primary)' : ''"
            v-html="link.label" />
        </div>
      </div>
    </div>

    <!-- Modal d'édition d'utilisateur -->
    <div v-if="showModal" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,28,51,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(3px);">
      <div class="card animate-fade-up" style="width:100%; max-width:480px; margin:20px;">
        <div class="card-header">
          <h2 class="card-title">Modifier l'utilisateur</h2>
          <button @click="closeModal" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--color-text-muted)">✕</button>
        </div>
        <div class="card-body">
          <form @submit.prevent="submit">
            <div class="form-group">
              <label class="form-label">Nom complet <span class="required">*</span></label>
              <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }" required />
              <div v-if="errors.name" class="form-error">{{ errors.name }}</div>
            </div>

            <div class="form-group">
              <label class="form-label">Adresse email <span class="required">*</span></label>
              <input v-model="form.email" type="email" class="form-control" :class="{ error: errors.email }" required />
              <div v-if="errors.email" class="form-error">{{ errors.email }}</div>
            </div>

            <div class="form-group">
              <label class="form-label">Rôle d'accès <span class="required">*</span></label>
              <select v-model="form.role" class="form-control" :class="{ error: errors.role }" required>
                <option value="">Sélectionner un rôle...</option>
                <option v-for="role in roles" :key="role" :value="role">
                  {{ getRoleLabel(role) }}
                </option>
              </select>
              <div v-if="errors.role" class="form-error">{{ errors.role }}</div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
              <button type="button" @click="closeModal" class="btn btn-outline">Annuler</button>
              <button type="submit" class="btn btn-accent" :disabled="form.processing">
                <span v-if="form.processing">Enregistrement...</span>
                <span v-else>✓ Enregistrer</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  users:   Object,
  roles:   Array,
  filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingUserId = ref(null);

const form = useForm({
  name:  '',
  email: '',
  role:  '',
});

const errors = form.errors;

// Labels des rôles
const roleLabels = {
  admin:         'Administrateur',
  chef_projet:   'Chef de Projet',
  collaborateur: 'Collaborateur',
};

const getRoleLabel = (roleName) => roleLabels[roleName] || roleName;

const getRoleBadgeClass = (roleName) => {
  if (roleName === 'admin') return 'badge-danger';
  if (roleName === 'chef_projet') return 'badge-info';
  return 'badge-neutral';
};

let debounce;
const applyFilters = () => {
  clearTimeout(debounce);
  debounce = setTimeout(() => {
    router.get(route('users.index'), { search: search.value }, {
      preserveState: true, replace: true,
    });
  }, 350);
};

const clearFilters = () => {
  search.value = '';
  router.get(route('users.index'));
};

const openEditModal = (user) => {
  editingUserId.value = user.id;
  form.name = user.name;
  form.email = user.email;
  form.role = user.role || '';
  form.clearErrors();
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingUserId.value = null;
  form.reset();
};

const submit = () => {
  form.put(route('users.update', editingUserId.value), {
    onSuccess: () => closeModal(),
  });
};

const deleteUser = (id) => {
  if (confirm('Voulez-vous vraiment supprimer cet utilisateur ?')) {
    router.delete(route('users.destroy', id));
  }
};
</script>
