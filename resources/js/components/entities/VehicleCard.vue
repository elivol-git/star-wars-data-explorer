<template>
  <div :class="{ 'vehicle-card': true, highlighted }">
    <div class="header">
      <h3 class="vehicle-title">{{ item.name }}</h3>
      <div class="badge">{{ item.vehicle_class }}</div>
    </div>

    <div class="vehicle-info">
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
      <div class="spec-item">
        <span class="spec-label">Length</span>
        <span class="spec-value">{{ item.length ? item.length + ' m' : '—' }}</span>
      </div>
      <div class="spec-item">
        <span class="spec-label">Max Speed</span>
        <span class="spec-value">{{ item.max_atmosphering_speed ? item.max_atmosphering_speed + ' km/h' : '—' }}</span>
      </div>
      <div class="spec-item">
        <span class="spec-label">Crew</span>
        <span class="spec-value">{{ item.crew || '—' }}</span>
      </div>
      <div class="spec-item">
        <span class="spec-label">Passengers</span>
        <span class="spec-value">{{ item.passengers || '—' }}</span>
      </div>
    </div>

    <div class="vehicle-info">
      <div v-if="item.cost_in_credits" class="info-row">
        <span class="label">COST</span>
        <span class="value">{{ item.cost_in_credits.toLocaleString() }} credits</span>
      </div>
      <div v-if="item.cargo_capacity" class="info-row">
        <span class="label">CARGO</span>
        <span class="value">{{ item.cargo_capacity.toLocaleString() }} kg</span>
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
.vehicle-card {
  background: #111;
  border: 1px solid #333;
  border-radius: 10px;
  padding: 20px;
  transition: border-color 0.2s;
}

.vehicle-card.highlighted {
  border-left: 3px solid #ffd700;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.vehicle-title {
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

.vehicle-info {
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

.spec-label {
  color: #aaa;
  font-size: 12px;
  letter-spacing: 1px;
}

.spec-value {
  color: #ddd;
  font-weight: 600;
}
</style>
