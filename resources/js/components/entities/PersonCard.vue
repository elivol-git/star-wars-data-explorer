<template>
  <div :class="{ 'person-card': true, highlighted }">
    <h3 class="person-title">{{ item.name }}</h3>

    <div class="quick-stats">
      <span v-if="item.birth_year" class="stat">
        <span class="stat-label">Born</span>
        <span class="stat-value">{{ item.birth_year }}</span>
      </span>
      <span v-if="item.gender" class="stat">
        <span class="stat-label">Gender</span>
        <span class="stat-value">{{ item.gender }}</span>
      </span>
    </div>

    <div class="physical-grid">
      <div v-if="item.height" class="phys-item">
        <span class="phys-label">Height</span>
        <span class="phys-value">{{ item.height }} cm</span>
      </div>
      <div v-if="item.mass" class="phys-item">
        <span class="phys-label">Mass</span>
        <span class="phys-value">{{ item.mass }} kg</span>
      </div>
    </div>

    <div class="appearance-section">
      <div v-if="item.hair_color" class="appearance-row">
        <span class="label">HAIR</span>
        <span class="value">{{ item.hair_color }}</span>
      </div>
      <div v-if="item.skin_color" class="appearance-row">
        <span class="label">SKIN</span>
        <span class="value">{{ item.skin_color }}</span>
      </div>
      <div v-if="item.eye_color" class="appearance-row">
        <span class="label">EYES</span>
        <span class="value">{{ item.eye_color }}</span>
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
.person-card {
  background: #111;
  border: 1px solid #333;
  border-radius: 10px;
  padding: 20px;
  transition: border-color 0.2s;
}

.person-card.highlighted {
  border-left: 3px solid #ffd700;
}

.person-title {
  color: #ffd700;
  font-size: 20px;
  font-weight: 700;
  margin: 0 0 12px 0;
}

.quick-stats {
  display: flex;
  gap: 20px;
  margin-bottom: 12px;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255, 215, 0, 0.2);
}

.stat {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.stat-label {
  color: #aaa;
  font-size: 11px;
  letter-spacing: 1px;
}

.stat-value {
  color: #ffd700;
  font-weight: 700;
  font-size: 14px;
}

.physical-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin: 12px 0;
  padding: 12px;
  background: rgba(255, 215, 0, 0.05);
  border-radius: 6px;
}

.phys-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.phys-label {
  color: #aaa;
  font-size: 12px;
  letter-spacing: 1px;
}

.phys-value {
  color: #ddd;
  font-weight: 600;
}

.appearance-section {
  margin-top: 12px;
}

.appearance-row {
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
</style>
