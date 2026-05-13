<template>
  <div v-if="show" class="image-modal-overlay" @click="closeModal">
    <div class="image-modal-content" @click.stop>
      <button class="modal-close" @click="closeModal">✕</button>
      <div class="image-container">
        <img :src="imageUrl" :alt="entityName" />
      </div>
      <div class="image-info">
        <h3>{{ entityName }}</h3>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'

defineProps({
  show: Boolean,
  imageUrl: String,
  entityName: String,
})

const emit = defineEmits(['close'])

const closeModal = () => {
  emit('close')
}

const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    closeModal()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.image-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.2s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.image-modal-content {
  position: relative;
  background: #1a1a1a;
  border-radius: 12px;
  max-width: 90vw;
  max-height: 90vh;
  overflow: auto;
  display: flex;
  flex-direction: column;
}

.modal-close {
  position: absolute;
  top: 12px;
  right: 12px;
  background: rgba(255, 215, 0, 0.2);
  border: 1px solid rgba(255, 215, 0, 0.3);
  color: #ffd700;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  transition: background 0.2s;
}

.modal-close:hover {
  background: rgba(255, 215, 0, 0.3);
}

.image-container {
  padding: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-container img {
  max-width: 100%;
  max-height: 70vh;
  border-radius: 8px;
}

.image-info {
  padding: 0 24px 24px;
  text-align: center;
}

.image-info h3 {
  color: #ffd700;
  margin: 0 0 8px 0;
  font-size: 18px;
}

.source-attribution {
  color: #aaa;
  font-size: 12px;
  margin: 0;
}

@media (max-width: 768px) {
  .image-container {
    padding: 12px;
  }

  .image-info {
    padding: 0 12px 12px;
  }
}
</style>
