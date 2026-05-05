<template>
    <div class="ai-results">

        <div v-if="loading" class="text">
            Thinking...
        </div>

        <div v-else-if="error" class="text error">
            {{ error }}
        </div>

        <div v-else>

            <!-- MIXED RESULTS -->
            <div v-if="entity === 'mixed'">

                <div
                    v-for="group in mixedGroups"
                    :key="group.type"
                    class="group"
                >

                    <h2 class="group-title">
                        {{ group.type }}
                    </h2>

                    <div class="planet-grid">

                        <!-- FILMS -->
                        <template v-if="group.type === 'films'">

                            <EntityCard
                                v-for="x in group.items"
                                :key="x.id"
                                :type="group.type"
                                :item="x"
                            />

                        </template>

                        <!-- PLANETS (standalone or from films) -->
                        <template
                            v-if="group.type === 'planets' || (group.type === 'films' && planetsFromFilms(group.items).length)"
                        >

                            <PlanetCard
                                v-for="p in group.type === 'films' ? planetsFromFilms(group.items) : group.items"
                                :key="p.id"
                                :planet="p"
                                :match="p.match"
                                :keywords="p.keywords"
                            />

                        </template>

                        <!-- OTHER ENTITIES (starships/vehicles/species/people) -->
                        <template v-else>

                            <EntityCard
                                v-for="x in group.items"
                                :key="x.id"
                                :type="group.type"
                                :item="x"
                            />

                        </template>

                    </div>

                </div>

                <!-- RELATED PLANETS (for mixed with secondary entities) -->
                <div v-if="relatedPlanets?.length" class="planets-section">
                    <h2 class="group-title">Related Planets</h2>
                    <div class="grid">
                        <PlanetCard
                            v-for="p in relatedPlanets"
                            :key="p.id"
                            :planet="p"
                            :match="p.match"
                            :keywords="p.keywords"
                        />
                    </div>
                </div>

            </div>


            <!-- SINGLE ENTITY -->
            <div v-else>

                <div
                    v-if="entity === 'films' ? filmPlanets.length === 0 : data?.length === 0"
                    class="empty"
                >
                    No results found
                </div>

                <div v-else>

                    <!-- PRIMARY ENTITY (vehicle/starship/species) -->
                    <div v-if="['vehicles', 'starships', 'species'].includes(entity)" class="grid">

                        <EntityCard
                            v-for="x in data"
                            :key="x.id"
                            :type="entity"
                            :item="x"
                            :highlight="true"
                        />

                    </div>

                    <!-- RELATED PLANETS -->
                    <div v-if="relatedPlanets?.length" class="planets-section">
                        <h2 class="group-title">Related Planets</h2>
                        <div class="grid">
                            <PlanetCard
                                v-for="p in relatedPlanets"
                                :key="p.id"
                                :planet="p"
                                :match="p.match"
                                :keywords="p.keywords"
                            />
                        </div>
                    </div>

                    <!-- DEFAULT GRID (planets/films) -->
                    <div v-else class="grid">

                        <template v-if="entity === 'planets' || entity === 'films'">

                            <PlanetCard
                                v-for="p in entity === 'films' ? filmPlanets : data"
                                :key="p.id"
                                :planet="p"
                                :match="p.match"
                                :keywords="p.keywords"
                            />

                        </template>

                        <template v-else>

                            <EntityCard
                                v-for="x in data"
                                :key="x.id"
                                :type="entity"
                                :item="x"
                            />

                        </template>

                    </div>

                </div>

            </div>

        </div>

    </div>
</template>

<script setup>

import { ref, computed, onMounted } from "vue"
import PlanetCard from "./PlanetCard.vue"
import EntityCard from "./entities/EntityCard.vue"

const entity = ref(null)
const data = ref(null)
const relatedPlanets = ref(null)
const loading = ref(true)
const error = ref(null)

function normalizeList(value) {
    if (Array.isArray(value)) return value
    return value?.data ?? []
}

function planetsFromFilms(films) {
    if (!Array.isArray(films)) return []

    const seen = new Set()
    const planets = []

    for (const film of films) {
        for (const planet of normalizeList(film?.planets)) {
            if (!planet?.id || seen.has(planet.id)) continue

            seen.add(planet.id)
            planets.push({
                ...planet,
                match: planet.match ?? {
                    film: {
                        id: film.id,
                        title: film.title
                    }
                }
            })
        }
    }

    return planets
}

const filmPlanets = computed(() => {
    if (entity.value !== "films") return []
    return planetsFromFilms(data.value)
})

const mixedGroups = computed(() => {
    if (entity.value !== "mixed" || !data.value || typeof data.value !== "object") {
        return []
    }

    const groups = []

    for (const [type, items] of Object.entries(data.value)) {
        const list = Array.isArray(items) ? items : []

        if (type === "films") {
            if (planetsFromFilms(list).length > 0) {
                groups.push({ type, items: list })
            }
            continue
        }

        if (list.length > 0) {
            groups.push({ type, items: list })
        }
    }

    return groups
})

async function loadResults(q){

    loading.value = true
    error.value = null

    try{

        const res = await fetch(`/api/ai-search?q=${encodeURIComponent(q)}`)
        const json = await res.json()

        if(!res.ok){
            throw new Error(json?.error || "API Error")
        }

        entity.value = json.entity
        data.value = json.data
        relatedPlanets.value = json.relatedPlanets ?? null

    }catch(e){

        error.value = e.message

    }finally{

        loading.value = false

    }

}

onMounted(()=>{

    const params = new URLSearchParams(window.location.search)
    const q = params.get("q")

    if(q){
        loadResults(q)
    }else{
        loading.value = false
    }

})

</script>

<style scoped>
.planets-section {
    margin-top: 40px;
}

.planets-section .group-title {
    display: block !important;
    margin-bottom: 20px;
    font-size: 1.5em;
    color: #ffd700;
    border-bottom: 2px solid #ffd700;
    padding-bottom: 10px;
}
</style>
