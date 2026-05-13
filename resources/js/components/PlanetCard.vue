<template>
    <ImageModal :show="showImageModal" :imageUrl="planet.modal_image_url" :entityName="planet.name" @close="showImageModal = false" />
    <div class="planet-card">
        <div v-if="planet.image_url" class="entity-image-thumbnail" @click="showImageModal = true">
            <img :src="planet.image_url" :alt="planet.name" />
        </div>

        <!-- TITLE -->
        <h3 class="planet-title">{{ planet.name }}</h3>

        <!-- MATCH EXPLANATION -->
        <div v-if="matchExists" class="search-match">

            <div class="match-title">🔎 MATCHED VIA</div>

            <div v-if="match.vehicle" class="match-item">
                🚀
                <span class="match-entity" @click="openEntity(match.vehicle, 'vehicles')">
          {{ match.vehicle.name }}
        </span>
            </div>

            <div v-if="match.starship" class="match-item">
                🛰
                <span class="match-entity" @click="openEntity(match.starship, 'starships')">
          {{ match.starship.name }}
        </span>
            </div>

            <div v-if="match.species" class="match-item">
                🧬
                <span class="match-entity" @click="openEntity(match.species, 'species')">
          {{ match.species.name }}
        </span>
            </div>

            <div v-if="match.film" class="match-item">
                🎬
                <span class="match-entity" @click="openEntity(match.film, 'films')">
          {{ match.film.title }}
        </span>
            </div>

            <div v-if="match.person" class="match-item">
                👤
                <span class="match-entity" @click="openEntity(match.person, 'people')">
          {{ match.person.name }}
        </span>
            </div>

        </div>

        <!-- PLANET INFO -->
        <div class="planet-info">

            <div
                v-for="(value, key) in planetInfo"
                :key="key"
                class="planet-info-row"
            >
                <span class="label">{{ format(key) }}</span>

                <span
                    class="value"
                    :class="{ highlight: match?.property === key }"
                >
          {{ value }}
        </span>

            </div>

        </div>

        <!-- FILMS / RESIDENTS -->
        <PlanetTabs
            :planet="planet"
            @open-entity="openEntity"
            @open-list="openList"
        />

        <!-- ENTITY MODAL -->
        <EntityModal
            v-if="activeEntity"
            :entity="activeEntity"
            :entity-type="activeEntityType"
            @close="activeEntity = null; activeEntityType = null"
        />

    </div>
</template>

<script setup>
import { ref, computed } from "vue"
import PlanetTabs from "./PlanetTabs.vue"
import EntityModal from "./entities/EntityModal.vue"
import ImageModal from "./ImageModal.vue"

const props = defineProps({
    planet: Object
})

const showImageModal = ref(false)
const activeEntity = ref(null)
const activeEntityType = ref(null)

const openEntity = (entity, type) => {
    activeEntity.value = entity
    if (type) {
        activeEntityType.value = type
    } else {
        activeEntityType.value = detectEntityType(entity)
    }
}

const openList = (list) => {
    activeEntity.value = list[0]
    activeEntityType.value = detectEntityType(list[0])
}

const detectEntityType = (entity) => {
    if (!entity) return null
    if (entity.episode_id !== undefined || entity.opening_crawl !== undefined) return 'films'
    if (entity.vehicle_class !== undefined) return 'vehicles'
    if (entity.starship_class !== undefined) return 'starships'
    if (entity.classification !== undefined || entity.designation !== undefined) return 'species'
    if (entity.birth_year !== undefined || entity.gender !== undefined) return 'people'
    return null
}

/*
|--------------------------------------------------------------------------
| MATCH CONTEXT
|--------------------------------------------------------------------------
*/

const match = computed(() => props.planet.match || null)

const matchExists = computed(() => {
    if (!match.value) return false

    return (
        match.value.vehicle ||
        match.value.starship ||
        match.value.species ||
        match.value.film ||
        match.value.person
    )
})

/*
|--------------------------------------------------------------------------
| FORMAT LABEL
|--------------------------------------------------------------------------
*/

function format(key) {
    return key.replace(/_/g, " ").toUpperCase()
}

/*
|--------------------------------------------------------------------------
| PLANET INFO
|--------------------------------------------------------------------------
*/

const planetInfo = computed(() => {

    const exclude = [
        "id",
        "created",
        "edited",
        "url",
        "films",
        "people",
        "residents",
        "match",
        "created_at",
        "updated_at",
        "image_url",
        "modal_image_url"
    ]

    return Object.fromEntries(
        Object.entries(props.planet)
            .filter(([k, v]) =>
                !exclude.includes(k) &&
                typeof v !== "object"
            )
    )

})
</script>

<style scoped>

.planet-card {
    position: relative;
    background: #111;
    border: 1px solid #333;
    padding: 20px;
    border-radius: 10px;
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

.planet-title {
    font-size: 22px;
    margin-bottom: 10px;
}

/* MATCH SECTION */

.search-match {
    background: rgba(255,215,0,0.08);
    border-left: 3px solid #ffd700;
    padding: 10px;
    margin-bottom: 15px;
}

.match-title {
    font-size: 12px;
    color: #aaa;
    letter-spacing: 1px;
    margin-bottom: 6px;
}

.match-item {
    margin: 4px 0;
}

.match-entity {
    color: #ffd700;
    font-weight: 600;
    cursor: pointer;
}

.match-entity:hover {
    text-decoration: underline;
}

/* PLANET INFO */

.planet-info-row {
    display: flex;
    justify-content: space-between;
    padding: 3px 0;
}

.label {
    color: #aaa;
}

.value {
    color: #ddd;
}

/* PROPERTY HIGHLIGHT */

.highlight {
    color: #ffd700;
    font-weight: 700;
}

</style>
