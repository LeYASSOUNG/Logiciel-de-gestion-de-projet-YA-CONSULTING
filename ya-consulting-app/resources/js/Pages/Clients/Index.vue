<template>
  <AppLayout title="Clients">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <PageHeader title="Clients" :description="`${clients.total} client${clients.total > 1 ? 's' : ''} enregistré${clients.total > 1 ? 's' : ''}`">
        <template v-slot:actions>
          <Button v-if="canManage" variant="accent" @click="openCreateModal">
            <template v-slot:icon-left>
              <Icon name="plus" :size="16" />
            </template>
            Nouveau client
          </Button>
        </template>
      </PageHeader>

      <!-- Erreurs éventuelles -->
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
            placeholder="Rechercher un client..."
            @input="applyFilters"
          />
        </div>
        <button v-if="search" class="btn-reset" @click="clearFilters" title="Effacer">
          <Icon name="x-mark" :size="16" />
        </button>
      </div>

      <!-- Tableau -->
      <Card>
        <div v-if="clients.data.length" class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>Client</th>
                <th>Email de contact</th>
                <th>Téléphone</th>
                <th>Adresse</th>
                <th v-if="canManage" class="text-right" style="width:120px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="client in clients.data" :key="client.id">
                <td>
                  <div class="client-cell">
                    <div class="avatar avatar-md" :class="getAvatarColor(client.name)">
                      {{ initials(client.name) }}
                    </div>
                    <div>
                      <div class="client-name">{{ client.name }}</div>
                      <div v-if="client.company" class="client-company">{{ client.company }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <a v-if="client.contact_email" :href="`mailto:${client.contact_email}`" class="email-link">
                    <Icon name="envelope" :size="13" />
                    {{ client.contact_email }}
                  </a>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <a v-if="client.contact_phone" :href="`tel:${client.contact_phone}`" class="phone-link">
                    {{ client.contact_phone }}
                  </a>
                  <span v-else class="text-muted">—</span>
                </td>
                <td class="text-muted ellipsis" style="max-width:200px;">{{ client.address || '—' }}</td>
                <td v-if="canManage" class="text-right">
                  <div class="action-group">
                    <button class="action-btn" title="Modifier" @click="openEditModal(client)">
                      <Icon name="pencil-square" :size="14" />
                    </button>
                    <button class="action-btn danger" title="Supprimer" @click="deleteClient(client.id)">
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
            title="Aucun client trouvé"
            description="Essayez de modifier vos filtres ou de créer un nouveau client."
            icon="users"
          >
            <template v-slot:action>
              <Button v-if="canManage" variant="accent" size="sm" @click="openCreateModal">
                <template v-slot:icon-left><Icon name="plus" :size="14" /></template>
                Ajouter un client
              </Button>
            </template>
          </EmptyState>
        </div>

        <!-- Pagination -->
        <Pagination :links="clients.links" />
      </Card>
    </div>

    <!-- Modal Formulaire (Créer / Modifier) -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal-card animate-scale-in">
            <!-- Header modal -->
            <div class="modal-header">
              <div class="modal-header-icon">
                <Icon name="user-circle" :size="20" />
              </div>
              <h2 class="modal-title">{{ modalTitle }}</h2>
              <button class="modal-close" @click="closeModal" title="Fermer">
                <Icon name="x-mark" :size="18" />
              </button>
            </div>

            <div class="modal-body">
              <form @submit.prevent="submit">
                <FormField label="Nom du contact" :error="errors.name" required>
                  <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }"
                    placeholder="Ex: Moustapha Diop" required />
                </FormField>

                <FormField label="Entreprise / Organisation">
                  <input v-model="form.company" type="text" class="form-control" placeholder="Ex: YA CONSULTING" />
                </FormField>

                <div class="form-grid">
                  <FormField label="Email de contact" :error="errors.contact_email">
                    <input v-model="form.contact_email" type="email" class="form-control"
                      :class="{ error: errors.contact_email }" placeholder="client@entreprise.sn" />
                  </FormField>

                  <FormField label="Téléphone">
                    <input v-model="form.contact_phone" type="text" class="form-control"
                      placeholder="+221 77 000 00 00" />
                  </FormField>
                </div>

                <FormField label="Adresse physique">
                  <input v-model="form.address" type="text" class="form-control"
                    placeholder="Ex: Plateau, Abidjan" />
                </FormField>

                <FormField label="Notes / Commentaires">
                  <textarea v-model="form.notes" class="form-control" rows="2"
                    placeholder="Informations complémentaires..." />
                </FormField>

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
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  clients: Object,
  filters: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const canManage = computed(() =>
  user.value.roles?.includes('admin') || user.value.roles?.includes('chef_projet')
);

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingClientId = ref(null);

const modalTitle = computed(() => editingClientId.value ? 'Modifier le client' : 'Ajouter un client');

const form = useForm({
  name:          '',
  company:       '',
  contact_email: '',
  contact_phone: '',
  address:       '',
  notes:         '',
});

const errors = form.errors;

// Avatars
const avatarColors = ['avatar-primary', 'avatar-blue', 'avatar-emerald', 'avatar-purple', 'avatar-rose', 'avatar-orange', 'avatar-teal'];
const getAvatarColor = (name) => avatarColors[(name?.charCodeAt(0) || 0) % avatarColors.length];
const initials = (name) => (name || '?').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);

const applyFilters = () => {
  router.get(route('clients.index'), { search: search.value }, { preserveState: true, replace: true });
};

const clearFilters = () => {
  search.value = '';
  router.get(route('clients.index'));
};

const openCreateModal = () => {
  editingClientId.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (client) => {
  editingClientId.value = client.id;
  form.name          = client.name;
  form.company       = client.company || '';
  form.contact_email = client.contact_email || '';
  form.contact_phone = client.contact_phone || '';
  form.address       = client.address || '';
  form.notes         = client.notes || '';
  form.clearErrors();
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingClientId.value = null;
  form.reset();
};

const submit = () => {
  if (editingClientId.value) {
    form.put(route('clients.update', editingClientId.value), { onSuccess: () => closeModal() });
  } else {
    form.post(route('clients.store'), { onSuccess: () => closeModal() });
  }
};

const deleteClient = (id) => {
  if (confirm('Voulez-vous vraiment supprimer ce client ?')) {
    router.delete(route('clients.destroy', id));
  }
};
</script>

<style scoped>
/* Cellule Client */
.client-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.client-name {
  font-weight: 600;
  color: var(--color-primary);
  font-size: 0.9rem;
}

.client-company {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-top: 1px;
}

/* Liens */
.email-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: var(--color-accent-dark);
  font-size: 0.875rem;
  text-decoration: none;
  transition: color 0.15s ease;
}
.email-link:hover { color: var(--color-primary); }

.phone-link {
  color: var(--color-text);
  font-size: 0.875rem;
  text-decoration: none;
}
.phone-link:hover { color: var(--color-accent-dark); }

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
  flex-shrink: 0;
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
  max-width: 560px;
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
  flex-shrink: 0;
}
.modal-close:hover {
  background: var(--color-bg-light);
  color: var(--color-text);
}

.modal-body {
  padding: 24px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-md);
  margin-top: var(--space-xl);
  padding-top: var(--space-lg);
  border-top: 1px solid var(--color-border-light);
}

/* Transition Modal */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-active .modal-card,
.modal-leave-active .modal-card {
  transition: transform 0.25s cubic-bezier(0.16,1,0.3,1), opacity 0.2s ease;
}
.modal-enter-from .modal-card {
  transform: scale(0.96) translateY(8px);
  opacity: 0;
}
.modal-leave-to .modal-card {
  transform: scale(0.96) translateY(8px);
  opacity: 0;
}
</style>
