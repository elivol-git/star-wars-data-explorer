<template>
  <div :class="{ 'species-card': true, highlighted }">
    <div class="header">
      <h3 class="species-title">{{ item.name }}</h3>
      <div class="badge">{{ item.classification || 'Unknown' }}</div>
    </div>

    <div class="species-info">
      <div v-if="item.designation" class="info-row">
        <span class="label">DESIGNATION</span>
        <span class="value">{{ item.designation }}</span>
      </div>
      <div v-if="item.language" class="info-row">
        <span class="label">LANGUAGE</span>
        <span class="value">{{ item.language }}</span>
      </div>
    </div>

    <div class="biology-grid">
      <div class="bio-item">
        <span class="bio-label">Avg Height</span>
        <span class="bio-value">{{ item.average_height ? item.average_height + ' cm' : '—' }}</span>
      </div>
      <div class="bio-item">
        <span class="bio-label">Avg Lifespan</span>
        <span class="bio-value">{{ item.average_lifespan ? item.average_lifespan + ' years' : '—' }}</span>
      </div>
    </div>

    <div class="colors-section">
      <div v-if="item.skin_colors" class="color-row">
        <span class="emoji">🎨</span>
        <span class="color-value">{{ item.skin_colors }}</span>
      </div>
      <div v-if="item.hair_colors" class="color-row">
        <span class="emoji">💇</span>
        <span class="color-value">{{ item.hair_colors }}</span>
      </div>
      <div v-if="item.eye_colors" class="color-row">
        <span class="emoji">👁️</span>
        <span class="color-value">{{ item.eye_colors }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  item: Object,
  highlight: Boolean,
})
</script>

<style scoped>
.species-card {
  background: #111;
  border: 1px solid #333;
  border-radius: 10px;
  padding: 20px;
  transition: border-color 0.2s;
}

.species-card.highlighted {
  border-left: 3px solid #ffd700;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.species-title {
  color: #ffd700;
  font-size: 14px;
  font-weight: 700;
  margin: 0;
}

.badge {
  display: inline-block;
  background: #222;
  color: #aaa;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  white-space: nowrap;
  flex-shrink: 0;
}

.species-info {
  margin-bottom: 12px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
}

.label {
  color: #aaa;
  font-size: 12px;
  letter-spacing: 1px;
}

.value {
  color: #ddd;
}

.biology-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin: 12px 0;
  padding: 12px;
  background: rgba(255, 215, 0, 0.05);
  border-radius: 6px;
}

.bio-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.bio-label {
  color: #aaa;
  font-size: 12px;
  letter-spacing: 1px;
}

.bio-value {
  color: #ddd;
  font-weight: 600;
}

.colors-section {
  margin-top: 12px;
}

.color-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 0;
}

.emoji {
  font-size: 16px;
  flex-shrink: 0;
}

.color-value {
  color: #ddd;
  font-size: 13px;
}
</style>
