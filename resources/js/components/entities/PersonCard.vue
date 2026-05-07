<template>
  <ImageModal :show="showImageModal" :imageUrl="item.image_url" :entityName="item.name" @close="showImageModal = false" />
  <div :class="{ 'person-card': true, highlighted }">
    <div v-if="item.image_url" class="entity-image-thumbnail" @click="showImageModal = true">
      <img :src="item.image_url" :alt="item.name" />
    </div>
    <h3 class="person-title">{{ item.name }}</h3>

    <div class="quick-stats" v-if="item.birth_year || item.gender">
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
import { ref } from 'vue'
import ImageModal from '../ImageModal.vue'

defineProps({
  item: Object,
  highlight: Boolean,
})

const showImageModal = ref(false)
</script>

<style scoped>
.person-card {
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

.person-card.highlighted {
  border-left: 3px solid #ffd700;
}

.person-title {
  color: #ffd700;
  font-size: 14px;
  font-weight: 700;
  margin: 0 0 8px 0;
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
