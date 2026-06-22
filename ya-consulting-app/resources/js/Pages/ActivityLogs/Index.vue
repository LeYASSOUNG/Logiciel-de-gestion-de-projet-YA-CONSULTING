<template>
  <AppLayout title="Journal d'activités">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <PageHeader title="Journal d'activités" description="Historique complet et traçabilité des modifications du système">
      </PageHeader>

      <!-- Tableau -->
      <Card>
        <div v-if="activities.data.length" class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 180px">Date &amp; Heure</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Ressource</th>
                <th>Identifiant</th>
                <th>Détails</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="act in activities.data" :key="act.id">
                <td class="text-muted" style="font-size:.8rem;">{{ act.created_at }}</td>
                <td>
                  <strong style="color:var(--color-primary)">{{ act.causer_name }}</strong>
                </td>
                <td>
                  <span class="badge" :class="getActionBadgeClass(act.description)">
                    {{ act.description }}
                  </span>
                </td>
                <td>
                  <span v-if="act.subject_type" class="text-bold" style="font-size:.82rem;">
                    {{ translateSubjectType(act.subject_type) }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span v-if="act.subject_id" class="badge badge-neutral" style="font-family:monospace;">
                    #{{ act.subject_id }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <div v-if="act.properties && Object.keys(act.properties).length" class="properties-list">
                    <span v-for="(val, key) in act.properties" :key="key" class="property-tag">
                      <strong>{{ key }}</strong>: {{ val }}
                    </span>
                  </div>
                  <span v-else class="text-muted">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else style="padding: var(--space-xl);">
          <EmptyState
            title="Aucune activité enregistrée"
            description="Le journal est actuellement vide."
            icon="clipboard-document-list"
          />
        </div>

        <!-- Pagination -->
        <Pagination :links="activities.links" />
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/Card.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
  activities: Object,
});

const getActionBadgeClass = (description) => {
  const desc = description.toLowerCase();
  if (desc.includes('créé') || desc.includes('enregistrée')) return 'badge-success';
  if (desc.includes('modifié') || desc.includes('mise à jour')) return 'badge-info';
  if (desc.includes('supprimé') || desc.includes('suppression')) return 'badge-danger';
  return 'badge-neutral';
};

const translateSubjectType = (type) => {
  const map = {
    Project: 'Projet',
    Expense: 'Dépense',
    Client: 'Client',
    User: 'Utilisateur',
    ExpenseCategory: 'Catégorie de dépense',
    MonthlyReport: 'Rapport mensuel',
  };
  return map[type] || type;
};
</script>

<style scoped>
.properties-list {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.property-tag {
  font-size: 0.72rem;
  background: rgba(26, 43, 74, 0.04);
  color: var(--color-text);
  padding: 2px 6px;
  border-radius: 4px;
  border: 1px solid rgba(26, 43, 74, 0.08);
}
</style>
