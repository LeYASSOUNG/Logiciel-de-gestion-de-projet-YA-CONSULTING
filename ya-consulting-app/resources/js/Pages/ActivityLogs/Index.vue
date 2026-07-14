<template>
  <AppLayout title="Journal d'activités">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <PageHeader title="Journal d'activités" description="Historique complet et traçabilité des modifications du système" />

      <!-- Timeline -->
      <Card>
        <div v-if="activities.data.length" class="activity-timeline">
          <div
            v-for="(act, index) in activities.data"
            :key="act.id"
            class="timeline-item"
            :class="getTimelineClass(act.description)"
          >
            <!-- Point + ligne -->
            <div class="timeline-indicator">
              <div class="timeline-dot">
                <Icon :name="getActionIcon(act.description)" :size="13" />
              </div>
              <div v-if="index < activities.data.length - 1" class="timeline-line" />
            </div>

            <!-- Contenu -->
            <div class="timeline-content">
              <div class="timeline-header">
                <div class="timeline-actor">
                  <div class="actor-avatar" :class="getAvatarColor(act.causer_name)">
                    {{ initials(act.causer_name) }}
                  </div>
                  <span class="actor-name">{{ act.causer_name || 'Système' }}</span>
                </div>

                <div class="timeline-right">
                  <span class="action-badge" :class="getActionBadgeClass(act.description)">
                    {{ act.description }}
                  </span>
                  <span class="timeline-time">
                    <Icon name="clock" :size="11" />
                    {{ act.created_at }}
                  </span>
                </div>
              </div>

              <div v-if="act.subject_type" class="timeline-subject">
                <Icon name="document-text" :size="13" />
                <span class="subject-type">{{ translateSubjectType(act.subject_type) }}</span>
                <span v-if="act.subject_id" class="subject-id">#{{ act.subject_id }}</span>
              </div>

              <div v-if="act.properties && Object.keys(act.properties).length" class="timeline-props">
                <span v-for="(val, key) in act.properties" :key="key" class="prop-chip">
                  <strong>{{ key }}</strong>: {{ val }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div v-else>
          <EmptyState
            title="Aucune activité enregistrée"
            description="Le journal est actuellement vide. Les actions futures seront tracées ici."
            icon="clipboard-document-list"
          />
        </div>

        <!-- Pagination -->
        <div class="timeline-pagination">
          <Pagination :links="activities.links" />
        </div>
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
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  activities: Object,
});

// Avatar
const avatarColors = ['avatar-primary', 'avatar-blue', 'avatar-emerald', 'avatar-purple', 'avatar-rose', 'avatar-orange', 'avatar-teal'];
const getAvatarColor = (name) => avatarColors[(name?.charCodeAt(0) || 0) % avatarColors.length];
const initials = (name) => (name || '?').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);

const getTimelineClass = (description) => {
  const desc = (description || '').toLowerCase();
  if (desc.includes('créé') || desc.includes('enregistrée') || desc.includes('enregistré')) return 'tl-success';
  if (desc.includes('modifié') || desc.includes('mise à jour') || desc.includes('mis à jour')) return 'tl-info';
  if (desc.includes('supprimé') || desc.includes('suppression')) return 'tl-danger';
  return 'tl-neutral';
};

const getActionIcon = (description) => {
  const desc = (description || '').toLowerCase();
  if (desc.includes('créé') || desc.includes('enregistrée') || desc.includes('enregistré')) return 'plus-circle';
  if (desc.includes('modifié') || desc.includes('mise à jour')) return 'pencil-square';
  if (desc.includes('supprimé') || desc.includes('suppression')) return 'trash';
  return 'information-circle';
};

const getActionBadgeClass = (description) => {
  const desc = (description || '').toLowerCase();
  if (desc.includes('créé') || desc.includes('enregistrée') || desc.includes('enregistré')) return 'action-success';
  if (desc.includes('modifié') || desc.includes('mise à jour')) return 'action-info';
  if (desc.includes('supprimé') || desc.includes('suppression')) return 'action-danger';
  return 'action-neutral';
};

const translateSubjectType = (type) => {
  const map = {
    Project: 'Projet',
    Expense: 'Dépense',
    Client: 'Client',
    User: 'Utilisateur',
    ExpenseCategory: 'Catégorie',
    MonthlyReport: 'Rapport',
    Payment: 'Paiement',
  };
  return map[type] || type;
};
</script>

<style scoped>
/* Timeline */
.activity-timeline {
  padding: var(--space-lg) var(--space-xl);
  display: flex;
  flex-direction: column;
  gap: 0;
}

.timeline-item {
  display: flex;
  gap: 16px;
  position: relative;
}

/* Indicateur (point + ligne) */
.timeline-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
  width: 28px;
}

.timeline-dot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 2px solid;
  z-index: 1;
}

.tl-success .timeline-dot {
  background: rgba(5,150,105,.1);
  border-color: var(--color-success);
  color: var(--color-success);
}
.tl-info .timeline-dot {
  background: rgba(2,132,199,.1);
  border-color: var(--color-info);
  color: var(--color-info);
}
.tl-danger .timeline-dot {
  background: rgba(220,38,38,.1);
  border-color: var(--color-danger);
  color: var(--color-danger);
}
.tl-neutral .timeline-dot {
  background: var(--color-bg-light);
  border-color: var(--color-border-light);
  color: var(--color-text-muted);
}

.timeline-line {
  width: 2px;
  flex: 1;
  min-height: 20px;
  background: var(--color-border-light);
  margin: 2px 0;
}

/* Contenu */
.timeline-content {
  flex: 1;
  background: rgba(248,250,252,0.5);
  border: 1px solid var(--color-border-light);
  border-radius: var(--radius-md);
  padding: 14px 16px;
  margin-bottom: 12px;
  transition: var(--transition);
}

.timeline-content:hover {
  background: white;
  box-shadow: var(--shadow-sm);
}

.timeline-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.timeline-actor {
  display: flex;
  align-items: center;
  gap: 10px;
}

.actor-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  font-weight: 700;
  font-size: .7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-family: var(--font-heading);
}

/* Réutilisation des classes avatar de app.css */
.avatar-primary  { background: linear-gradient(135deg, #0F1C33, #1A2B4A); color: #D4B154; }
.avatar-gold     { background: linear-gradient(135deg, #B8963E, #D4B154); color: #0F1C33; }
.avatar-blue     { background: linear-gradient(135deg, #0284C7, #0EA5E9); color: white; }
.avatar-emerald  { background: linear-gradient(135deg, #059669, #10B981); color: white; }
.avatar-purple   { background: linear-gradient(135deg, #7C3AED, #8B5CF6); color: white; }
.avatar-rose     { background: linear-gradient(135deg, #BE123C, #F43F5E); color: white; }
.avatar-orange   { background: linear-gradient(135deg, #D97706, #F97316); color: white; }
.avatar-teal     { background: linear-gradient(135deg, #0D9488, #14B8A6); color: white; }

.actor-name {
  font-weight: 600;
  color: var(--color-primary);
  font-size: 0.875rem;
}

.timeline-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 5px;
}

.action-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: var(--radius-full);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.3px;
}

.action-success { background: rgba(5,150,105,.12); color: var(--color-success); }
.action-info    { background: rgba(2,132,199,.12);  color: var(--color-info); }
.action-danger  { background: rgba(220,38,38,.12);  color: var(--color-danger); }
.action-neutral { background: var(--color-bg-light); color: var(--color-text-muted); }

.timeline-time {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  color: var(--color-text-light);
}

/* Ressource concernée */
.timeline-subject {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  font-size: 0.8rem;
  color: var(--color-text-muted);
}

.subject-type {
  font-weight: 600;
  color: var(--color-text);
}

.subject-id {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.72rem;
  background: var(--color-bg-light);
  padding: 1px 6px;
  border-radius: var(--radius-xs);
  color: var(--color-text-muted);
  border: 1px solid var(--color-border-light);
}

/* Propriétés */
.timeline-props {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 8px;
}

.prop-chip {
  font-size: 0.72rem;
  background: rgba(15,28,51,.04);
  color: var(--color-text);
  padding: 2px 8px;
  border-radius: var(--radius-xs);
  border: 1px solid rgba(15,28,51,.08);
}

/* Pagination */
.timeline-pagination {
  padding: 0 var(--space-xl) var(--space-lg);
}
</style>
