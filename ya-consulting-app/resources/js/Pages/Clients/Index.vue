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
        <div style="position: relative; flex: 1; min-width: 200px; max-width: 300px;">
          <input
            v-model="search"
            class="form-control"
            style="padding-left: 36px;"
            placeholder="Rechercher un client..."
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
        <div v-if="clients.data.length" class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Entreprise</th>
                <th>Email de contact</th>
                <th>Téléphone</th>
                <th>Adresse</th>
                <th v-if="canManage" style="width:120px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="client in clients.data" :key="client.id">
                <td><strong style="color:var(--color-primary);">{{ client.name }}</strong></td>
                <td>{{ client.company || '—' }}</td>
                <td>
                  <a v-if="client.contact_email" :href="`mailto:${client.contact_email}`" class="link-primary">
                    {{ client.contact_email }}
                  </a>
                  <span v-else>—</span>
                </td>
                <td>{{ client.contact_phone || '—' }}</td>
                <td class="text-muted" style="font-size:.8rem; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                  {{ client.address || '—' }}
                </td>
                <td v-if="canManage" style="text-align:right">
                  <div style="display:flex; gap:6px; justify-content:flex-end;">
                    <Button variant="outline" size="sm" style="padding: 6px 10px;" @click="openEditModal(client)">
                      <Icon name="pencil-square" :size="14" />
                    </Button>
                    <Button variant="outline" size="sm" class="text-danger" style="padding: 6px 10px;" @click="deleteClient(client.id)">
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
            title="Aucun client trouvé"
            description="Essayez de modifier vos filtres ou de créer un nouveau client."
            icon="users"
          />
        </div>

        <!-- Pagination -->
        <Pagination :links="clients.links" />
      </Card>
    </div>

    <!-- Modal Formulaire (Créer / Modifier) -->
    <div v-if="showModal" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,28,51,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(3px);">
      <div class="card animate-fade-up" style="width:100%; max-width:560px; margin:20px;">
        <div class="card-header">
          <h2 class="card-title">{{ modalTitle }}</h2>
          <button @click="closeModal" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--color-text-muted)">✕</button>
        </div>
        <div class="card-body">
          <form @submit.prevent="submit">
            <FormField label="Nom du contact" :error="errors.name" required>
              <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }" placeholder="Ex: Moustapha Diop" required />
            </FormField>

            <FormField label="Entreprise / Organisation">
              <input v-model="form.company" type="text" class="form-control" placeholder="Ex: YA CONSULTING" />
            </FormField>

            <div class="form-grid">
              <FormField label="Email de contact" :error="errors.contact_email">
                <input v-model="form.contact_email" type="email" class="form-control" :class="{ error: errors.contact_email }" placeholder="Ex: client@entreprise.sn" />
              </FormField>

              <FormField label="Téléphone">
                <input v-model="form.contact_phone" type="text" class="form-control" placeholder="Ex: +221 77 000 00 00" />
              </FormField>
            </div>

            <FormField label="Adresse physique">
              <input v-model="form.address" type="text" class="form-control" placeholder="Ex: Palmerai, la rue ministre, Abidjan" />
            </FormField>

            <FormField label="Notes / Commentaires">
              <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Informations complémentaires sur le client..." />
            </FormField>

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

const canManage = computed(() => {
  return user.value.roles?.includes('admin') || user.value.roles?.includes('chef_projet');
});

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

let debounce;
const applyFilters = () => {
  clearTimeout(debounce);
  debounce = setTimeout(() => {
    router.get(route('clients.index'), { search: search.value }, {
      preserveState: true, replace: true,
    });
  }, 350);
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
  form.name = client.name;
  form.company = client.company || '';
  form.contact_email = client.contact_email || '';
  form.contact_phone = client.contact_phone || '';
  form.address = client.address || '';
  form.notes = client.notes || '';
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
    form.put(route('clients.update', editingClientId.value), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('clients.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteClient = (id) => {
  if (confirm('Voulez-vous vraiment supprimer ce client ?')) {
    router.delete(route('clients.destroy', id));
  }
};
</script>
