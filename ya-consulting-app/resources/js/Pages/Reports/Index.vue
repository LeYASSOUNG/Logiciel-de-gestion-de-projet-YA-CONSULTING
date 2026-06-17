<template>
  <AppLayout title="Rapports financiers">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <h1>Rapports financiers</h1>
          <p>Générez et téléchargez les analyses de rentabilité mensuelles</p>
        </div>
      </div>

      <!-- Erreurs éventuelles -->
      <div v-if="$page.props.errors.error" class="alert alert-danger mb-lg">
        {{ $page.props.errors.error }}
      </div>

      <div class="grid-3 mb-xl">
        <!-- Formulaire de génération (Col 1) -->
        <div class="card" style="grid-column: span 1">
          <div class="card-header">
            <h2 class="card-title">Générer un rapport</h2>
          </div>
          <div class="card-body">
            <form @submit.prevent="generateReport">
              <div class="form-group">
                <label class="form-label">Mois de l'activité</label>
                <select v-model="form.month" class="form-control" required>
                  <option v-for="(name, index) in months" :key="index" :value="index">
                    {{ name }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Année</label>
                <select v-model="form.year" class="form-control" required>
                  <option v-for="year in years" :key="year" :value="year">
                    {{ year }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Format d'export</label>
                <div style="display:flex; gap:16px; margin-top:8px;">
                  <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                    <input type="radio" v-model="form.file_type" value="pdf" /> PDF (.pdf)
                  </label>
                  <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                    <input type="radio" v-model="form.file_type" value="excel" /> CSV / Excel (.csv)
                  </label>
                </div>
              </div>

              <button type="submit" class="btn btn-accent style-btn" style="width:100%; justify-content:center; margin-top:var(--space-md);" :disabled="form.processing">
                <span v-if="form.processing">Génération en cours...</span>
                <span v-else>⚙ Générer le rapport</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Explication / Conseil (Col 2 & 3) -->
        <div class="card" style="grid-column: span 2; display:flex; flex-direction:column; justify-content:center; padding:var(--space-xl); background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%); color:#fff; border:none;">
          <div style="max-width:520px;">
            <span style="font-size:.7rem; text-transform:uppercase; color:var(--color-accent); font-weight:700; letter-spacing:1px;">YA CONSULTING · Finance</span>
            <h2 style="color:#fff; border-left-color:var(--color-accent); margin-top:var(--space-sm); font-size:1.4rem;">Comment sont calculés les rapports ?</h2>
            <p style="font-size:.875rem; color:var(--sidebar-text); line-height:1.6; margin-top:var(--space-md);">
              Le système analyse tous les projets actifs ou clôturés sur la période sélectionnée pour agréger les budgets globaux.
              Il calcule ensuite la somme des décaissements et dépenses réelles enregistrées sur ce même mois.
            </p>
            <div style="display:flex; gap:var(--space-lg); margin-top:var(--space-lg);">
              <div style="flex:1;">
                <div style="font-weight:700; color:var(--color-accent); font-size:1rem;">Marge Brute</div>
                <div style="font-size:.75rem; color:var(--sidebar-text)">Budget alloué moins dépenses réelles de la période.</div>
              </div>
              <div style="flex:1;">
                <div style="font-weight:700; color:var(--color-accent); font-size:1rem;">Traçabilité</div>
                <div style="font-size:.75rem; color:var(--sidebar-text)">Chaque rapport généré reste archivé pour assurer un historique fiable.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Historique des rapports -->
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Historique des rapports archivés ({{ reports.total }})</h2>
        </div>
        <div class="table-container">
          <table class="table">
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
              <tr v-for="rep in reports.data" :key="rep.id">
                <td>
                  <strong style="color:var(--color-primary);">{{ rep.name }}</strong>
                </td>
                <td style="font-weight:600;">{{ rep.month }} {{ rep.year }}</td>
                <td>
                  <span class="badge" :class="rep.file_type === 'pdf' ? 'badge-info' : 'badge-neutral'">
                    {{ rep.file_type.toUpperCase() }}
                  </span>
                </td>
                <td style="color:var(--color-text-muted);">{{ rep.generated_by }}</td>
                <td style="color:var(--color-text-muted); font-size:.8rem;">{{ rep.generated_at }}</td>
                <td class="amount-neutral text-right">{{ fmt(rep.total_budget) }}</td>
                <td style="color:var(--color-danger);" class="text-right">{{ fmt(rep.total_expenses) }}</td>
                <td :class="rep.net_profit >= 0 ? 'amount-positive' : 'amount-negative'" class="text-right">
                  {{ rep.net_profit >= 0 ? '+' : '' }}{{ fmt(rep.net_profit) }}
                </td>
                <td style="text-align:right">
                  <a :href="route('reports.download', rep.id)" class="btn btn-primary btn-sm">
                    Télécharger
                  </a>
                </td>
              </tr>
              <tr v-if="!reports.data.length">
                <td colspan="9" style="text-align:center; padding:40px; color:var(--color-text-muted);">
                  Aucun rapport généré pour le moment
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="reports.last_page > 1"
          style="display:flex; justify-content:center; gap:6px; padding:16px; border-top:1px solid var(--color-border);">
          <Link v-for="link in reports.links" :key="link.label"
            :href="link.url || '#'"
            class="btn btn-outline btn-sm"
            :style="link.active ? 'background:var(--color-primary); color:#fff; border-color:var(--color-primary)' : ''"
            v-html="link.label" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
.style-btn {
  border-radius: var(--radius-md);
  font-weight: 700;
}
</style>
