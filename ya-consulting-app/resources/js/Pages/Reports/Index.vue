<template>
  <AppLayout title="Rapports financiers">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <div class="slide-down">
        <PageHeader title="Rapports financiers" description="Générez et téléchargez les analyses de rentabilité mensuelles" />
      </div>

      <!-- Erreurs éventuelles -->
      <div v-if="$page.props.errors.error" class="alert alert-danger mb-lg slide-down delay-1">
        {{ $page.props.errors.error }}
      </div>

      <div class="grid-3 fade-in-up delay-1" style="margin-bottom: 4rem;">
        <!-- Formulaire de génération (Col 1) -->
        <Card title="Générer un rapport" style="grid-column: span 1">
          <form @submit.prevent="generateReport">
            <FormField label="Mois de l'activité">
              <select v-model="form.month" class="form-control" required>
                <option v-for="(name, index) in months" :key="index" :value="index">
                  {{ name }}
                </option>
              </select>
            </FormField>

            <FormField label="Année">
              <select v-model="form.year" class="form-control" required>
                <option v-for="year in years" :key="year" :value="year">
                  {{ year }}
                </option>
              </select>
            </FormField>

            <FormField label="Format d'export">
              <div class="format-cards">
                <label class="format-card" :class="{ selected: form.file_type === 'pdf' }">
                  <input type="radio" v-model="form.file_type" value="pdf" style="display:none" />
                  <div class="format-card-icon pdf">PDF</div>
                  <div class="format-card-label">PDF</div>
                  <div class="format-card-sub">Document portable (.pdf)</div>
                  <div class="format-card-check">
                    <svg width="10" height="8" viewBox="0 0 10 8" fill="none">
                      <path d="M1 4L3.5 6.5L9 1" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </div>
                </label>
                <label class="format-card" :class="{ selected: form.file_type === 'excel' }">
                  <input type="radio" v-model="form.file_type" value="excel" style="display:none" />
                  <div class="format-card-icon excel">CSV</div>
                  <div class="format-card-label">Excel / CSV</div>
                  <div class="format-card-sub">Tableur (.csv)</div>
                  <div class="format-card-check">
                    <svg width="10" height="8" viewBox="0 0 10 8" fill="none">
                      <path d="M1 4L3.5 6.5L9 1" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </div>
                </label>
              </div>
            </FormField>

            <Button type="submit" variant="accent" style="width:100%; justify-content:center; margin-top:var(--space-md);" :disabled="form.processing">
              <span v-if="form.processing">Génération en cours...</span>
              <span v-else style="display: flex; align-items: center; gap: 8px;">
                <Icon name="cog-6-tooth" :size="16" />
                Générer le rapport
              </span>
            </Button>
          </form>
        </Card>

        <!-- Explication / Conseil (Col 2 & 3) -->
        <div class="info-panel" style="grid-column: span 2;">
          <div class="info-panel-eyebrow">YA CONSULTING · Finance</div>
          <h2 class="info-panel-title">Comment sont calculés les rapports ?</h2>
          <p class="info-panel-text">
            Le système analyse tous les projets actifs ou clôturés sur la période sélectionnée pour agréger les budgets globaux.
            Il calcule ensuite la somme des décaissements et dépenses réelles enregistrées sur ce même mois.
          </p>
          <div class="info-panel-features">
            <div class="info-panel-feature">
              <div class="info-panel-feature-icon">
                <Icon name="presentation-chart-line" :size="20" />
              </div>
              <div class="info-panel-feature-title">Marge Brute</div>
              <div class="info-panel-feature-desc">Budget alloué moins dépenses réelles de la période.</div>
            </div>
            <div class="info-panel-feature">
              <div class="info-panel-feature-icon">
                <Icon name="document-text" :size="20" />
              </div>
              <div class="info-panel-feature-title">Traçabilité</div>
              <div class="info-panel-feature-desc">Chaque rapport généré reste archivé pour assurer un historique fiable.</div>
            </div>
            <div class="info-panel-feature">
              <div class="info-panel-feature-icon">
                <Icon name="shield-check" :size="20" />
              </div>
              <div class="info-panel-feature-title">Sécurisé</div>
              <div class="info-panel-feature-desc">Données chiffrées, accès restreint aux rôles autorisés.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Séparateur visuel -->
      <div style="width: 100%; height: 1px; background: rgba(0,0,0,0.06); margin-bottom: 2rem;"></div>

      <!-- Historique des rapports -->
      <div class="fade-in-up delay-2">
        <Card :title="`Historique des rapports archivés (${reports.total})`">
          <div v-if="reports.data.length" class="table-container">
            <table class="table modern-table-hover">
              <thead>
                <tr>
                  <th>Nom du rapport</th>
                  <th>Période</th>
                  <th>Format</th>
                  <th>Créateur</th>
                  <th>Généré le</th>
                  <th class="text-right">Budget global</th>
                  <th class="text-right">Dépenses globales</th>
                  <th class="text-right">Bénéfice Net</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="rep in reports.data" :key="rep.id" class="table-row-animate">
                  <td>
                    <strong style="color:var(--color-primary);">{{ rep.name }}</strong>
                  </td>
                  <td style="font-weight:600;">{{ rep.month }} {{ rep.year }}</td>
                  <td>
                    <span class="badge" :class="rep.file_type === 'pdf' ? 'badge-info' : 'badge-neutral'">
                      {{ rep.file_type.toUpperCase() }}
                    </span>
                  </td>
                  <td class="text-muted">{{ rep.generated_by }}</td>
                  <td class="text-muted" style="font-size:.8rem;">{{ rep.generated_at }}</td>
                  <td class="amount-neutral text-right">{{ fmt(rep.total_budget) }}</td>
                  <td style="color:var(--color-danger);" class="text-right">{{ fmt(rep.total_expenses) }}</td>
                  <td :class="rep.net_profit >= 0 ? 'amount-positive' : 'amount-negative'" class="text-right">
                    {{ rep.net_profit >= 0 ? '+' : '' }}{{ fmt(rep.net_profit) }}
                  </td>
                  <td style="text-align:right">
                    <Button as="a" :href="route('reports.download', rep.id)" variant="primary" size="sm">
                      Télécharger
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else style="padding: var(--space-xl);">
            <EmptyState
              title="Aucun rapport généré pour le moment"
              description="Utilisez le formulaire ci-dessus pour configurer et générer votre premier rapport financier."
              icon="document-text"
            />
          </div>

          <!-- Pagination -->
          <Pagination :links="reports.links" />
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  reports: Object,
  months:  Object,
  years:   Array,
});

const form = useForm({
  month:     new Date().getMonth() + 1,
  year:      new Date().getFullYear(),
  file_type: 'pdf',
});

const fmt = (v) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v ?? 0);

const generateReport = () => {
  form.post(route('reports.generate'));
};
</script>

<style scoped>
/* Animations and subtle hover enhancements ONLY */
.slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
@keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.modern-table-hover tr.table-row-animate { transition: background 0.2s; }
.modern-table-hover tr.table-row-animate:hover { background: rgba(248, 250, 252, 0.8); }
</style>
