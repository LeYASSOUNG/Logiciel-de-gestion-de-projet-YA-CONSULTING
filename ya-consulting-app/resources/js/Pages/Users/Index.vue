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
        <div style="position: relative; flex: 1; min-width: 200px; max-width: 300px;">
          <input
            v-model="search"
            class="form-control"
            style="padding-left: 36px;"
            placeholder="Rechercher un utilisateur..."
            @input="applyFilters"
          />
          <Icon name="magnifying-glass" :size="16" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-text-light);" />
        </div>
        <Button v-if="search" variant="outline" size="sm" @click="clearFilters">
          <template v-slot:icon-left>
            <Icon name="x-mark" :size="14" />
          </template>
          Effacer
        </Button>
      </div>

      <!-- Tableau -->
      <Card>
        <div v-if="users.data.length" class="table-container">
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
                <td class="text-muted" style="font-size:.8rem;">{{ user.created_at }}</td>
                <td style="text-align:right">
                  <div style="display:flex; gap:6px; justify-content:flex-end;">
                    <Button variant="outline" size="sm" style="padding: 6px 10px;" @click="openEditModal(user)">
                      <Icon name="pencil-square" :size="14" />
                    </Button>
                    <Button v-if="user.id !== $page.props.auth.user.id" variant="outline" size="sm" class="text-danger" style="padding: 6px 10px;" @click="deleteUser(user.id)">
                      <Icon name="trash" :size="14" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else style="padding: var(--space-xl);">
          <EmptyState
            title="Aucun utilisateur trouvé"
            description="Essayez de modifier vos filtres ou d'ajouter un nouvel utilisateur."
            icon="users"
          />
        </div>

        <!-- Pagination -->
        <Pagination :links="users.links" />
      </Card>
    </div>

    <!-- Modal de gestion d'utilisateur -->
    <div v-if="showModal" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,28,51,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(3px);">
      <div class="card animate-fade-up" style="width:100%; max-width:480px; margin:20px;">
        <div class="card-header">
          <h2 class="card-title">{{ editingUserId ? "Modifier l'utilisateur" : "Ajouter un utilisateur" }}</h2>
          <button @click="closeModal" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--color-text-muted)">✕</button>
        </div>
        <div class="card-body">
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

            <FormField v-if="form.role === 'client'" label="Client Associé" :error="errors.client_id" required>
              <select v-model="form.client_id" class="form-control" :class="{ error: errors.client_id }" required>
                <option value="">Sélectionner le client...</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">
                  {{ client.name }}
                </option>
              </select>
            </FormField>

            <div v-if="!editingUserId" class="form-group">
              <FormField label="Mot de passe" :error="errors.password" required>
                <input v-model="form.password" type="password" class="form-control" :class="{ error: errors.password }" required />
              </FormField>
            </div>

            <div v-if="!editingUserId" class="form-group">
              <FormField label="Confirmer le mot de passe" :error="errors.password_confirmation" required>
                <input v-model="form.password_confirmation" type="password" class="form-control" :class="{ error: errors.password_confirmation }" required />
              </FormField>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
              <Button type="button" variant="outline" @click="closeModal">Annuler</Button>
              <Button type="submit" variant="accent" :disabled="form.processing">
                <span v-if="form.processing">Enregistrement...</span>
                <span v-else style="display: flex; align-items: center; gap: 8px;">
                  <Icon name="check" :size="16" />
                  Enregistrer
                </span>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
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
  client_id:             '',
  password:              '',
  password_confirmation: '',
});

const errors = form.errors;

// Labels des rôles
const roleLabels = {
  admin:         'Administrateur',
  chef_projet:   'Chef de Projet',
  collaborateur: 'Collaborateur',
  client:        'Client',
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

const openCreateModal = () => {
  editingUserId.value = null;
  form.reset();
  form.client_id = '';
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (user) => {
  editingUserId.value = user.id;
  form.name           = user.name;
  form.email          = user.email;
  form.role           = user.role || '';
  form.client_id      = user.client_id || '';
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
    form.put(route('users.update', editingUserId.value), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('users.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteUser = (id) => {
  if (confirm('Voulez-vous vraiment supprimer cet utilisateur ?')) {
    router.delete(route('users.destroy', id));
  }
};
</script>
