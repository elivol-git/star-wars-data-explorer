<template>
    <transition name="fade">
        <ImageModal v-if="entity && showImageModal" :show="showImageModal" :imageUrl="entity.modal_image_url" :entityName="entity.name || entity.title" @close="showImageModal = false" />
    </transition>
    <transition name="fade">
        <div
            v-if="entity"
            class="modal-window entity-tooltip"
            :style="style"
        >
            <button class="modal-close" @click="$emit('close')">×</button>

            <div v-if="entity.modal_image_url" class="tooltip-image" @click="showImageModal = true">
                <img :src="entity.modal_image_url" :alt="entity.name || entity.title" />
            </div>

            <h4 class="planet-title">
                {{ entity.name || entity.title }}
            </h4>

            <div class="planet-info">
                <div
                    v-for="(value, key) in info"
                    :key="key"
                    class="planet-info-row"
                >
                    <span class="label">{{ format(key) }}</span>
                    <span class="value">{{ value }}</span>
                </div>
            </div>

            <div class="entity-link more" @click="$emit('open', entity)">
                Open
            </div>
        </div>
    </transition>
</template>

<script setup>
import { computed, ref } from 'vue';
import ImageModal from '../ImageModal.vue';

const isMobile = computed(() =>
    window.matchMedia('(max-width: 768px)').matches
);

const props = defineProps({
    entity: Object,
    x: Number,
    y: Number
});

const showImageModal = ref(false);

const exclude = [
    'id','url',
    'films','people','residents',
    'vehicles','species','starships',
    'homeworld','homeworld_id',
    'created','edited','created_at','updated_at',
    'opening_crawl','image_url','modal_image_url'
];

const info = computed(() =>
    Object.fromEntries(
        Object.entries(props.entity || {})
            .filter(([k,v]) =>
                !exclude.includes(k) &&
                typeof v !== 'object' &&
                v
            )
    )
);

const style = computed(() => {
    if (isMobile.value) {
        return {
            position: 'fixed',
            left: '50%',
            bottom: '16px',
            transform: 'translateX(-50%)',
            width: 'calc(100% - 32px)',
            maxWidth: '480px',
            zIndex: 1001
        };
    }

    return {
        position: 'fixed',
        left: props.x + 'px',
        top: props.y + 'px',
        maxWidth: '360px',
        zIndex: 1001
    };
});


const format = (k) =>
    k.replace(/_/g,' ').toUpperCase();
</script>

<style scoped>
.entity-tooltip {
  position: relative;
}

.tooltip-image {
  position: relative;
  width: 100%;
  height: 150px;
  border-radius: 6px;
  border: 1px solid rgba(255, 215, 0, 0.3);
  overflow: hidden;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.2s;
  margin-bottom: 12px;
}

.tooltip-image:hover {
  opacity: 0.8;
  transform: scale(1.05);
}

.tooltip-image::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 12px;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.6));
  pointer-events: none;
}

.tooltip-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
