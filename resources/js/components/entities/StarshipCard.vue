<template>
  <ImageModal :show="showImageModal" :imageUrl="item.image_url" :entityName="item.name" @close="showImageModal = false" />
  <div :class="{ 'starship-card': true, highlighted }">
    <div v-if="item.image_url" class="entity-image-thumbnail" @click="showImageModal = true">
      <img :src="item.image_url" :alt="item.name" />
    </div>
    <div class="header">
      <h3 class="starship-title">{{ item.name }}</h3>
      <div class="badge">{{ item.starship_class }}</div>
    </div>

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
import { ref } from 'vue'
import ImageModal from '../ImageModal.vue'

defineProps({
  item: Object,
  highlight: Boolean,
})

const showImageModal = ref(false)
</script>

<style scoped>
.starship-card {
  position: relative;
  background: #111;
  border: 1px solid #333;
  border-radius: 10px;
  padding: 20px;
  transition: border-color 0.2s;
}

.entity-image-thumbnail {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 60px;
  height: 60px;
  border-radius: 6px;
  border: 1px solid rgba(255, 215, 0, 0.3);
  overflow: hidden;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.2s;
}

.entity-image-thumbnail:hover {
  opacity: 0.8;
  transform: scale(1.05);
}

.entity-image-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.starship-card.highlighted {
  border-left: 3px solid #ffd700;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.starship-title {
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
