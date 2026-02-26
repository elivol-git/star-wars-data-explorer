<template>
    <div class="card">
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

    return {
        id: props.item.id,
    }
})
</script>
