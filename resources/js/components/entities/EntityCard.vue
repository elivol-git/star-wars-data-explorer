<template>
    <div class="card" :class="{ highlighted: highlight }">
        <div v-if="item.image_url" class="card-image">
            <img :src="item.image_url" :alt="title" />
        </div>
        <div class="title">
            {{ title }}
        </div>

        <div class="meta">
            <div v-for="(v, k) in previewFields" :key="k" class="row">
                <span class="key">{{ k }}:</span>
                <span class="val">{{ v }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue"

const props = defineProps({
    type: String,
    item: Object,
    highlight: {
        type: Boolean,
        default: false
    }
})

const title = computed(() => {
    if (props.type === "films") return props.item.title
    return props.item.name || props.item.model || "Unknown"
})

const previewFields = computed(() => {
    if (props.type === "films") {
        return {
            director: props.item.director,
            producer: props.item.producer,
            release_date: props.item.release_date,
        }
    }

    if (props.type === "starships") {
        return {
            model: props.item.model,
            manufacturer: props.item.manufacturer,
            class: props.item.starship_class,
        }
    }

    if (props.type === "vehicles") {
        return {
            model: props.item.model,
            manufacturer: props.item.manufacturer,
            class: props.item.vehicle_class,
        }
    }

    if (props.type === "species") {
        return {
            classification: props.item.classification,
            language: props.item.language,
        }
    }

    if (props.type === "people") {
        return {
            birth_year: props.item.birth_year,
            gender: props.item.gender,
        }
    }

    return {
        id: props.item.id,
    }
})
</script>

<style scoped>
.card {
    position: relative;
    background: #111;
    border: 1px solid #333;
    padding: 20px;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.card-image {
    position: absolute;
    top: 0;
    right: 0;
    width: 50px;
    height: 50px;
    border-radius: 0 10px 0 10px;
    border: 1px solid rgba(255, 215, 0, 0.3);
    overflow: hidden;
}

.card-image::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 15px;
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.6));
    pointer-events: none;
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card .title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #ffd700;
}

.card .meta {
    font-size: 13px;
}

.card .row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
}

.card .key {
    color: #aaa;
    font-weight: 600;
}

.card .val {
    color: #ddd;
    text-align: right;
}

.card.highlighted {
    border-color: #ffd700;
    box-shadow: 0 0 0 3px #ffd700, 0 0 20px rgba(255, 215, 0, 0.5);
    transform: scale(1.02);
}
</style>
