<template>
  <AppLayout title="Utilisateurs">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <PageHeader title="Gestion des Utilisateurs" :description="`${users.total} utilisateur${users.total > 1 ? 's' : ''} enregistré${users.total > 1 ? 's' : ''}`">
        <template v-slot:actions>
          <Button variant="accent" @click="openCreateModal">
            <template v-slot:icon-left>
              <Icon name="plus" :size="16" />
            </template>
            Nouvel utilisateur
          </Button>
        </template>
      </PageHeader>

      <!-- Erreurs de suppression -->
      <div v-if="$page.props.errors.delete" class="alert alert-danger mb-lg">
        {{ $page.props.errors.delete }}
      </div>

      <!-- Filtres -->
      <div class="filters-bar">
        <div class="search-box" style="max-width: 320px;">
          <Icon name="magnifying-glass" :size="16" class="search-icon" />
          <input
            v-model="search"
            class="search-input"
            placeholder="Rechercher un utilisateur..."
            @input="applyFilters"
          />
        </div>
        <button v-if="search" class="btn-reset" @click="clearFilters" title="Effacer">
          <Icon name="x-mark" :size="16" />
        </button>
      </div>

      <!-- Tableau -->
      <Card>
        <div v-if="users.data.length" class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>Utilisateur</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Inscrit le</th>
                <th class="text-right" style="width:120px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in users.data" :key="u.id">
                <td>
                  <div class="user-cell">
                    <div class="avatar avatar-md" :class="getAvatarColor(u.name)">
                      {{ initials(u.name) }}
                    </div>
                    <div>
                      <div class="user-name">{{ u.name }}</div>
                      <div v-if="u.id === $page.props.auth.user.id" class="user-you-badge">Vous</div>
                    </div>
                  </div>
                </td>
                <td class="text-muted" style="font-size:.875rem;">{{ u.email }}</td>
                <td>
                  <span class="role-badge" :class="`role-${u.role}`">
                    {{ getRoleLabel(u.role) }}
                  </span>
                </td>
                <td class="text-muted" style="font-size:.8rem;">{{ u.created_at }}</td>
                <td class="text-right">
                  <div class="action-group">
                    <button class="action-btn" title="Modifier" @click="openEditModal(u)">
                      <Icon name="pencil-square" :size="14" />
                    </button>
                    <button
                      v-if="u.id !== $page.props.auth.user.id"
                      class="action-btn danger"
                      title="Supprimer"
                      @click="deleteUser(u.id)"
                    >
                      <Icon name="trash" :size="14" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else>
          <EmptyState
            title="Aucun utilisateur trouvé"
            description="Essayez de modifier vos filtres ou d'ajouter un nouvel utilisateur."
            icon="users"
          >
            <template v-slot:action>
              <Button variant="accent" size="sm" @click="openCreateModal">
                <template v-slot:icon-left><Icon name="plus" :size="14" /></template>
                Ajouter un utilisateur
              </Button>
            </template>
          </EmptyState>
        </div>

        <!-- Pagination -->
        <Pagination :links="users.links" />
      </Card>
    </div>

    <!-- Modal de gestion d'utilisateur -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal-card animate-scale-in">
            <div class="modal-header">
              <div class="modal-header-icon">
                <Icon name="user-circle" :size="20" />
              </div>
              <h2 class="modal-title">{{ editingUserId ? "Modifier l'utilisateur" : "Ajouter un utilisateur" }}</h2>
              <button class="modal-close" @click="closeModal" title="Fermer">
                <Icon name="x-mark" :size="18" />
              </button>
            </div>

            <div class="modal-body">
              <form @submit.prevent="submit">
                <FormField label="Nom complet" :error="errors.name" required>
                  <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }" required />
                </FormField>

                <FormField label="Adresse email" :error="errors.email" required>
                  <input v-model="form.email" type="email" class="form-control" :class="{ error: errors.email }" required />
                </FormField>

                <FormField label="Rôle d'accès" :error="errors.role" required>
                  <select v-model="form.role" class="form-control" :class="{ error: errors.role }" required>
                    <option value="">Sélectionner un rôle...</option>
                    <option v-for="role in roles" :key="role" :value="role">
                      {{ getRoleLabel(role) }}
                    </option>
                  </select>
                </FormField>

                <template v-if="!editingUserId">
                  <FormField label="Mot de passe" :error="errors.password" required>
                    <input v-model="form.password" type="password" class="form-control" :class="{ error: errors.password }" required />
                  </FormField>

                  <FormField label="Confirmer le mot de passe" :error="errors.password_confirmation" required>
                    <input v-model="form.password_confirmation" type="password" class="form-control" :class="{ error: errors.password_confirmation }" required />
                  </FormField>
                </template>

                <div class="modal-actions">
                  <Button type="button" variant="outline" @click="closeModal">Annuler</Button>
                  <Button type="submit" variant="accent" :disabled="form.processing">
                    <template v-if="form.processing">Enregistrement...</template>
                    <template v-else>
                      <Icon name="check" :size="16" /> Enregistrer
                    </template>
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  users:   Object,
  roles:   Array,
  clients: Array,
  filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingUserId = ref(null);

const form = useForm({
  name:                  '',
  email:                 '',
  role:                  '',
  password:              '',
  password_confirmation: '',
});

const errors = form.errors;

// Avatars
const avatarColors = ['avatar-primary', 'avatar-blue', 'avatar-emerald', 'avatar-purple', 'avatar-rose', 'avatar-orange', 'avatar-teal'];
const getAvatarColor = (name) => avatarColors[(name?.charCodeAt(0) || 0) % avatarColors.length];
const initials = (name) => (name || '?').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);

// Labels des rôles
const roleLabels = {
  admin:         'Administrateur',
  chef_projet:   'Chef de Projet',
  collaborateur: 'Collaborateur',
};

const getRoleLabel = (roleName) => roleLabels[roleName] || roleName;

let debounce;
const applyFilters = () => {
  clearTimeout(debounce);
  debounce = setTimeout(() => {
    router.get(route('users.index'), { search: search.value }, { preserveState: true, replace: true });
  }, 350);
};

const clearFilters = () => {
  search.value = '';
  router.get(route('users.index'));
};

const openCreateModal = () => {
  editingUserId.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (user) => {
  editingUserId.value = user.id;
  form.name  = user.name;
  form.email = user.email;
  form.role  = user.role || '';
  form.clearErrors();
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingUserId.value = null;
  form.reset();
};

const submit = () => {
  if (editingUserId.value) {
    form.put(route('users.update', editingUserId.value), { onSuccess: () => closeModal() });
  } else {
    form.post(route('users.store'), { onSuccess: () => closeModal() });
  }
};

const deleteUser = (id) => {
  if (confirm('Voulez-vous vraiment supprimer cet utilisateur ?')) {
    router.delete(route('users.destroy', id));
  }
};
</script>

<style scoped>
/* Cellule utilisateur */
.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-name {
  font-weight: 600;
  color: var(--color-primary);
  font-size: 0.9rem;
}

.user-you-badge {
  display: inline-block;
  font-size: 0.65rem;
  font-weight: 700;
  background: rgba(212,177,84,.15);
  color: var(--color-accent-dark);
  border-radius: var(--radius-full);
  padding: 1px 7px;
  margin-top: 2px;
  letter-spacing: 0.4px;
  text-transform: uppercase;
}

/* Boutons d'action */
.action-group {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  background: var(--color-bg-light);
  border: 1px solid var(--color-border-light);
  color: var(--color-text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}
.action-btn:hover {
  background: #eff6ff;
  color: #3b82f6;
  border-color: #bfdbfe;
}
.action-btn.danger:hover {
  background: #fee2e2;
  color: var(--color-danger);
  border-color: #fecaca;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 28, 51, 0.55);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 24px;
}

.modal-card {
  background: #fff;
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  width: 100%;
  max-width: 480px;
  overflow: hidden;
  border: 1px solid var(--color-border-light);
}

.modal-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-border-light);
  background: linear-gradient(to bottom, rgba(248,250,252,0.9), white);
}

.modal-header-icon {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-sm);
  background: rgba(212,177,84,.12);
  color: var(--color-accent-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.modal-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-primary);
  flex: 1;
  margin: 0;
}

.modal-close {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border-light);
  background: none;
  cursor: pointer;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}
.modal-close:hover {
  background: var(--color-bg-light);
  color: var(--color-text);
}

.modal-body { padding: 24px; }

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-md);
  margin-top: var(--space-xl);
  padding-top: var(--space-lg);
  border-top: 1px solid var(--color-border-light);
}

/* Transition Modal */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
