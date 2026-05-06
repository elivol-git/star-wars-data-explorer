<template>
  <div :class="{ 'starship-card': true, highlighted }">
    <h3 class="starship-title">{{ item.name }}</h3>

    <div class="badge">{{ item.starship_class }}</div>

    <div class="starship-info">
      <div class="info-row">
        <span class="label">MODEL</span>
        <span class="value">{{ item.model || '—' }}</span>
      </div>
      <div class="info-row">
        <span class="label">MANUFACTURER</span>
        <span class="value">{{ item.manufacturer || '—' }}</span>
      </div>
    </div>

    <div class="specs-grid">
      <div class="spec-item highlight">
        <span class="spec-label">Hyperdrive</span>
        <span class="spec-value">{{ item.hyperdrive_rating || '—' }}</span>
      </div>
      <div class="spec-item highlight">
        <span class="spec-label">MGLT</span>
        <span class="spec-value">{{ item.MGLT || '—' }}</span>
      </div>
      <div class="spec-item">
        <span class="spec-label">Crew</span>
        <span class="spec-value">{{ item.crew || '—' }}</span>
      </div>
      <div class="spec-item">
        <span class="spec-label">Passengers</span>
        <span class="spec-value">{{ item.passengers || '—' }}</span>
      </div>
      <div class="spec-item">
        <span class="spec-label">Length</span>
        <span class="spec-value">{{ item.length ? item.length + ' m' : '—' }}</span>
      </div>
      <div class="spec-item">
        <span class="spec-label">Max Speed</span>
        <span class="spec-value">{{ item.max_atmosphering_speed ? item.max_atmosphering_speed + ' km/h' : '—' }}</span>
      </div>
    </div>

    <div class="starship-info">
      <div v-if="item.cost_in_credits" class="info-row">
        <span class="label">COST</span>
        <span class="value">{{ item.cost_in_credits.toLocaleString() }} credits</span>
      </div>
      <div v-if="item.consumables" class="info-row">
        <span class="label">CONSUMABLES</span>
        <span class="value">{{ item.consumables }}</span>
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
.starship-card {
  background: #111;
  border: 1px solid #333;
  border-radius: 10px;
  padding: 20px;
  transition: border-color 0.2s;
}

.starship-card.highlighted {
  border-left: 3px solid #ffd700;
}

.starship-title {
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

.starship-info {
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

.specs-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin: 12px 0;
  padding: 12px;
  background: rgba(255, 215, 0, 0.05);
  border-radius: 6px;
}

.spec-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.spec-item.highlight {
  background: rgba(255, 215, 0, 0.1);
  padding: 8px;
  border-radius: 4px;
  border: 1px solid rgba(255, 215, 0, 0.2);
}

.spec-label {
  color: #aaa;
  font-size: 12px;
  letter-spacing: 1px;
}

.spec-value {
  color: #ddd;
  font-weight: 600;
}

.spec-item.highlight .spec-value {
  color: #ffd700;
}
</style>
