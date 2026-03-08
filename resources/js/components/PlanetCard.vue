<template>
    <div class="planet-card">

        <!-- TITLE -->
        <h3 class="planet-title">{{ planet.name }}</h3>

        <!-- MATCH EXPLANATION -->
        <div v-if="matchExists" class="search-match">

            <div class="match-title">🔎 MATCHED VIA</div>

            <div v-if="match.vehicle" class="match-item">
                🚀
                <span class="match-entity" @click="openEntity(match.vehicle)">
          {{ match.vehicle.name }}
        </span>
            </div>

            <div v-if="match.starship" class="match-item">
                🛰
                <span class="match-entity" @click="openEntity(match.starship)">
          {{ match.starship.name }}
        </span>
            </div>

            <div v-if="match.species" class="match-item">
                🧬
                <span class="match-entity" @click="openEntity(match.species)">
          {{ match.species.name }}
        </span>
            </div>

            <div v-if="match.film" class="match-item">
                🎬
                <span class="match-entity" @click="openEntity(match.film)">
          {{ match.film.title }}
        </span>
            </div>

            <div v-if="match.person" class="match-item">
                👤
                <span class="match-entity" @click="openEntity(match.person)">
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
            @close="activeEntity = null"
        />

    </div>
</template>

<script setup>
import { ref, computed } from "vue"
import PlanetTabs from "./PlanetTabs.vue"
import EntityModal from "./entities/EntityModal.vue"

const props = defineProps({
    planet: Object
})

const activeEntity = ref(null)

const openEntity = (entity) => {
    activeEntity.value = entity
}

const openList = (list) => {
    activeEntity.value = list[0]
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
        "updated_at"
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
    background: #111;
    border: 1px solid #333;
    padding: 20px;
    border-radius: 10px;
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
