<template>
  <!-- Composant de conteneur global "Carte" pour encapsuler visuellement des éléments -->
  <div class="card">

    <!-- Zone de l'en-tête (Header) de la carte -->
    <!-- Elle ne s'affiche que si un titre est passé en 'prop' ou si le slot 'header' est utilisé -->
    <div v-if="title || $slots.header" class="card-header">

      <!-- 'slot' par défaut pour l'en-tête, permet au parent de remplacer le <h2> par autre chose -->
      <slot name="header">
        <h2 class="card-title">{{ title }}</h2>
      </slot>

      <!-- Zone d'actions à droite de l'en-tête (pour y mettre des boutons "Ajouter", "Modifier", etc.) -->
      <div v-if="$slots.actions" class="card-header-actions">
        <slot name="actions" />
      </div>
    </div>

    <!-- Zone principale du contenu de la carte -->
    <div class="card-body">
      <!-- Le slot par défaut affichera tout le contenu passé à l'intérieur des balises <Card> ... </Card> -->
      <slot />
    </div>
  </div>
</template>

<script setup>
/**
 * Propriétés (Props) acceptées par ce composant.
 * - title: Titre texte par défaut de la carte, affiché automatiquement dans le header.
 */
defineProps({
  title: { type: String, default: '' },
});
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.card-header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
</style>
