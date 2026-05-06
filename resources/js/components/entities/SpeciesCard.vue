<template>
  <div :class="{ 'species-card': true, highlighted }">
    <h3 class="species-title">{{ item.name }}</h3>

    <div class="badge">{{ item.classification || 'Unknown' }}</div>

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
      <div v-if="item.skin_colors" class="color-group">
        <span class="color-label">SKIN</span>
        <div class="color-values">{{ item.skin_colors }}</div>
      </div>
      <div v-if="item.hair_colors" class="color-group">
        <span class="color-label">HAIR</span>
        <div class="color-values">{{ item.hair_colors }}</div>
      </div>
      <div v-if="item.eye_colors" class="color-group">
        <span class="color-label">EYES</span>
        <div class="color-values">{{ item.eye_colors }}</div>
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

.species-title {
  color: #ffd700;
  font-size: 20px;
  font-weight: 700;
  margin: 0 0 8px 0;
}

.badge {
  display: inline-block;
  background: #222;
  color: #aaa;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  margin-bottom: 12px;
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

.color-group {
  margin-bottom: 10px;
}

.color-label {
  display: block;
  color: #aaa;
  font-size: 12px;
  letter-spacing: 1px;
  margin-bottom: 4px;
}

.color-values {
  color: #ddd;
  padding-left: 8px;
  border-left: 2px solid rgba(255, 215, 0, 0.3);
}
</style>
