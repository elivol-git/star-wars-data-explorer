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
                    v-for="(items, type) in data"
                    :key="type"
                    class="group"
                >

                    <h2 class="group-title">
                        {{ type }}
                    </h2>

                    <div v-if="items.length === 0" class="empty">
                        No results
                    </div>

                    <div v-else class="grid">

                        <!-- PLANETS -->
                        <template
                            v-if="type === 'planets' || (type === 'films' && planetsFromFilms(items).length)"
                        >

                            <PlanetCard
                                v-for="p in type === 'films' ? planetsFromFilms(items) : items"
                                :key="p.id"
                                :planet="p"
                                :match="p.match"
                                :keywords="p.keywords"
                            />

                        </template>

                        <!-- OTHER ENTITIES -->
                        <template v-else>

                            <EntityCard
                                v-for="x in items"
                                :key="x.id"
                                :type="type"
                                :item="x"
                            />

                        </template>

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
</template>

<script setup>

import { ref, computed, onMounted } from "vue"
import PlanetCard from "./PlanetCard.vue"
import EntityCard from "./entities/EntityCard.vue"

const entity = ref(null)
const data = ref(null)
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
