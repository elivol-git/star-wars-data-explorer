<template>
  <ImageModal :show="showImageModal" :imageUrl="item.modal_image_url" :entityName="item.name" @close="showImageModal = false" />
  <div :class="{ 'species-card': true, highlighted: highlight }">
    <div v-if="item.image_url" class="entity-image-thumbnail" @click="showImageModal = true">
      <img :src="item.image_url" :alt="item.name" />
    </div>
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
import { ref } from 'vue'
import ImageModal from '../ImageModal.vue'

defineProps({
  item: Object,
  highlight: Boolean,
})

const showImageModal = ref(false)
</script>

<style scoped>
.species-card {
  position: relative;
  background: #111;
  border: 1px solid #333;
  border-radius: 10px;
  padding: 20px;
  transition: border-color 0.2s;
}

.entity-image-thumbnail {
  position: absolute;
  top: 0;
  right: 0;
  width: 60px;
  height: 60px;
  border-radius: 6px;
  border: 1px solid rgba(255, 215, 0, 0.3);
  overflow: hidden;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.2s;
}

.entity-image-thumbnail::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 20px;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.6));
  pointer-events: none;
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
