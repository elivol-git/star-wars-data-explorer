<template>
    <div class="ai-results">

        <!-- Results Only -->
        <div v-if="loading" class="text">
            Thinking...
        </div>

        <div v-else-if="error" class="text error">
            {{ error }}
        </div>

        <div v-else>
            <!-- Mixed results -->
            <div v-if="entity === 'mixed'">
                <div v-for="(items, type) in data" :key="type" class="group">

                    <div v-if="items.length === 0" class="empty">
                        No results
                    </div>

                    <div v-else class="grid">
                        <PlanetCard
                            v-if="type === 'planets'"
                            v-for="p in items"
                            :key="p.id"
                            :planet="p"
                        />

                        <EntityCard
                            v-else
                            v-for="x in items"
                            :key="x.id"
                            :type="type"
                            :item="x"
                        />
                    </div>

                </div>
            </div>

            <!-- Single entity results -->
            <div v-else>
                <div v-if="data?.length === 0" class="empty">
                    No results found
                </div>

                <div v-else class="grid">
                    <PlanetCard
                        v-if="entity === 'planets'"
                        v-for="p in data"
                        :key="p.id"
                        :planet="p"
                    />

                    <EntityCard
                        v-else
                        v-for="x in data"
                        :key="x.id"
                        :type="entity"
                        :item="x"
                    />
                </div>
            </div>

        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import PlanetCard from "./PlanetCard.vue"
import EntityCard from "./entities/EntityCard.vue"

const entity = ref(null)
const data = ref(null)
const loading = ref(true)
const error = ref(null)

async function loadResults(q) {
    loading.value = true
    error.value = null

    try {
        const res = await fetch(`/api/ai-search?q=${encodeURIComponent(q)}`)
        const json = await res.json()

        if (!res.ok) {
            throw new Error(json?.error || "API Error")
        }

        entity.value = json.entity
        data.value = json.data
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search)
    const q = params.get("q")

    if (q) {
        loadResults(q)
    } else {
        loading.value = false
    }
})
</script>
