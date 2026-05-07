<template>
  <ImageModal :show="showImageModal" :imageUrl="item.image_url" :entityName="item.title" @close="showImageModal = false" />
  <div :class="{ 'film-card': true, highlighted }">
    <div v-if="item.image_url" class="entity-image-thumbnail" @click="showImageModal = true">
      <img :src="item.image_url" :alt="item.title" />
    </div>
    <h3 class="film-title">
      <span v-if="item.episode_id" class="episode">EP{{ item.episode_id }}</span>
      <span>{{ item.title }}</span>
    </h3>

    <div class="film-info">
      <div class="info-row">
        <span class="label">DIRECTOR</span>
        <span class="value">{{ item.director || '—' }}</span>
      </div>
      <div class="info-row">
        <span class="label">PRODUCER</span>
        <span class="value">{{ item.producer || '—' }}</span>
      </div>
      <div class="info-row">
        <span class="label">RELEASE</span>
        <span class="value">{{ item.release_date ? formatDate(item.release_date) : '—' }}</span>
      </div>
    </div>

    <div v-if="item.opening_crawl" class="crawl-section">
      <div class="crawl-label">OPENING CRAWL</div>
      <div class="crawl-text">{{ truncateCrawl(item.opening_crawl) }}</div>
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

function formatDate(dateStr) {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

function truncateCrawl(crawl) {
  if (!crawl) return ''
  const lines = crawl.split('\n').map(l => l.trim()).filter(l => l)
  const truncated = lines.slice(0, 3).join(' ')
  return truncated.length > 200 ? truncated.substring(0, 200) + '…' : truncated
}
</script>

<style scoped>
.film-card {
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

.film-card.highlighted {
  border-left: 3px solid #ffd700;
}

.film-title {
  color: #ffd700;
  font-size: 14px;
  font-weight: 700;
  margin: 0 0 8px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.episode {
  background: #222;
  color: #aaa;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  letter-spacing: 1px;
  white-space: nowrap;
  flex-shrink: 0;
}

.film-info {
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

.crawl-section {
  margin-top: 12px;
  padding: 12px;
  background: rgba(255, 215, 0, 0.05);
  border-radius: 6px;
  border-left: 3px solid rgba(255, 215, 0, 0.2);
}

.crawl-label {
  color: #aaa;
  font-size: 11px;
  letter-spacing: 1px;
  margin-bottom: 8px;
}

.crawl-text {
  color: #ccc;
  font-style: italic;
  font-size: 13px;
  line-height: 1.4;
  opacity: 0.85;
}
</style>
