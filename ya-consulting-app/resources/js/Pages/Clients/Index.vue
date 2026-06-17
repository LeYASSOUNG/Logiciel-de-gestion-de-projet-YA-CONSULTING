<template>
  <AppLayout title="Clients">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <h1>Clients</h1>
          <p>{{ clients.total }} client{{ clients.total > 1 ? 's' : '' }} enregistré{{ clients.total > 1 ? 's' : '' }}</p>
        </div>
        <button v-if="canManage" @click="openCreateModal" class="btn btn-accent">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Nouveau client
        </button>
      </div>

      <!-- Erreurs éventuelles -->
      <div v-if="$page.props.errors.delete" class="alert alert-danger mb-lg">
        {{ $page.props.errors.delete }}
      </div>

      <!-- Filtres -->
      <div class="filters-bar">
        <input
          v-model="search"
          class="form-control"
          placeholder="🔍 Rechercher un client..."
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
                  <a v-if="client.contact_email" :href="`mailto:${client.contact_email}`" style="color:var(--color-primary-light); text-decoration:none;">
                    {{ client.contact_email }}
                  </a>
                  <span v-else>—</span>
                </td>
                <td>{{ client.contact_phone || '—' }}</td>
                <td style="color:var(--color-text-muted); font-size:.8rem; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                  {{ client.address || '—' }}
                </td>
                <td v-if="canManage" style="text-align:right">
                  <div style="display:flex; gap:6px; justify-content:flex-end;">
                    <button @click="openEditModal(client)" class="btn btn-outline btn-sm" style="padding:4px 8px;">
                      ✏️
                    </button>
                    <button @click="deleteClient(client.id)" class="btn btn-outline btn-sm text-danger" style="padding:4px 8px; border-color:var(--color-border)">
                      ✕
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!clients.data.length">
                <td colspan="6" style="text-align:center; padding:40px; color:var(--color-text-muted);">
                  Aucun client trouvé
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="clients.last_page > 1"
          style="display:flex; justify-content:center; gap:6px; padding:16px; border-top:1px solid var(--color-border);">
          <Link v-for="link in clients.links" :key="link.label"
            :href="link.url || '#'"
            class="btn btn-outline btn-sm"
            :style="link.active ? 'background:var(--color-primary); color:#fff; border-color:var(--color-primary)' : ''"
            v-html="link.label" />
        </div>
      </div>
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
            <div class="form-group">
              <label class="form-label">Nom du contact <span class="required">*</span></label>
              <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }" placeholder="Ex: Moustapha Diop" required />
              <div v-if="errors.name" class="form-error">{{ errors.name }}</div>
            </div>

            <div class="form-group">
              <label class="form-label">Entreprise / Organisation</label>
              <input v-model="form.company" type="text" class="form-control" placeholder="Ex: YA CONSULTING" />
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Email de contact</label>
                <input v-model="form.contact_email" type="email" class="form-control" :class="{ error: errors.contact_email }" placeholder="Ex: client@entreprise.sn" />
                <div v-if="errors.contact_email" class="form-error">{{ errors.contact_email }}</div>
              </div>

              <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input v-model="form.contact_phone" type="text" class="form-control" placeholder="Ex: +221 77 000 00 00" />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Adresse physique</label>
              <input v-model="form.address" type="text" class="form-control" placeholder="Ex: Almadies, Route de la plage, Dakar" />
            </div>

            <div class="form-group">
              <label class="form-label">Notes / Commentaires</label>
              <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Informations complémentaires sur le client..." />
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
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
