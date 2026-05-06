<template>
    <transition name="fade">
        <div
            v-if="stack.length"
            class="modal-backdrop"
            @click.self="closeAll"
        >
            <transition name="scale">
                <div
                    ref="modalRef"
                    class="modal-window"
                    :class="{ 'modal-wide': isFilm }"
                >
                    <button class="modal-close" @click="closeAll">×</button>

                    <div v-if="stack.length > 1" class="breadcrumb">
                        <span
                            v-for="(item, i) in stack"
                            :key="i"
                            class="crumb"
                            @click="goTo(i)"
                        >
                            {{ item.title || item.name }}
                            <span v-if="i < stack.length - 1"> › </span>
                        </span>
                    </div>

                    <!-- TYPE-SPECIFIC CARDS -->
                    <VehicleCard v-if="entityType === 'vehicles'" :item="entity" />
                    <StarshipCard v-else-if="entityType === 'starships'" :item="entity" />
                    <SpeciesCard v-else-if="entityType === 'species'" :item="entity" />
                    <PersonCard v-else-if="entityType === 'people'" :item="entity" />
                    <FilmCard v-else-if="entityType === 'films'" :item="entity" />

                    <!-- FALLBACK FOR PLANETS AND UNTYPED ENTITIES -->
                    <div v-else>
                        <h3 class="planet-title">
                            {{ entity.title || entity.name }}
                        </h3>

                        <!-- FILM -->
                        <div v-if="isFilm" class="film-info">
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

                            <div class="crawl">
                                {{ entity.opening_crawl }}
                            </div>
                        </div>

                        <!-- NON-FILM -->
                        <div v-else class="planet-info">
                            <div
                                v-for="(value, key) in info"
                                :key="key"
                                class="planet-info-row"
                            >
                                <span class="label">{{ format(key) }}</span>
                                <span class="value">{{ value }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- RELATED -->
                    <div v-if="columns.length" class="related-columns">
                        <div
                            v-for="col in columns"
                            :key="col.key"
                            class="related-column"
                        >
                            <div class="column-title">
                                {{ col.label }} ({{ col.items.length }})
                            </div>

                            <ul>
                                <li
                                    v-for="item in visibleItems(col)"
                                    :key="item.id"
                                    class="entity-link"
                                    @click.stop="openTooltip(item, $event)"
                                >
                                    {{ item.name || item.title }}
                                </li>
                            </ul>

                            <div
                                v-if="col.items.length > LIMIT"
                                class="entity-link more"
                                @click="toggleMore(col.key)"
                            >
                                {{ expanded[col.key] ? 'Less' : 'More…' }}
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <EntityTooltip
                :entity="tooltip.entity"
                :x="tooltip.x"
                :y="tooltip.y"
                @close="closeTooltip"
                @open="drillDown"
            />

        </div>
    </transition>
</template>

<script setup>
import EntityTooltip from './EntityTooltip.vue';
import VehicleCard from './VehicleCard.vue';
import StarshipCard from './StarshipCard.vue';
import SpeciesCard from './SpeciesCard.vue';
import PersonCard from './PersonCard.vue';
import FilmCard from './FilmCard.vue';

import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';

const LIMIT = 5;

const props = defineProps({
    entity: Object,
    entityType: String
});

const emit = defineEmits(['close']);

const stack = ref([props.entity]);
const expanded = ref({});
const modalRef = ref(null);
const loading = ref(false);

const entity = computed(() => stack.value.at(-1));

const detectEntityType = (ent) => {
    if (!ent) return null;
    if (ent.episode_id !== undefined || ent.opening_crawl !== undefined) return 'films';
    if (ent.vehicle_class !== undefined) return 'vehicles';
    if (ent.starship_class !== undefined) return 'starships';
    if (ent.classification !== undefined || ent.designation !== undefined) return 'species';
    if (ent.birth_year !== undefined || ent.gender !== undefined) return 'people';
    return null;
};

const fetchFullEntity = async (ent, type) => {
    const searchName = ent?.name || ent?.title;
    const entityId = ent?.id;

    if (!searchName) return;

    const fetchType = type || detectEntityType(ent);
    if (!fetchType) return;

    try {
        loading.value = true;
        const res = await fetch(`/api/ai-search?q=${encodeURIComponent(searchName)}`);
        if (res.ok) {
            const json = await res.json();

            if (json.entity === fetchType && Array.isArray(json.data)) {
                let found;
                if (entityId) {
                    found = json.data.find(item => item.id === entityId);
                } else {
                    found = json.data[0];
                }

                if (found) {
                    const idx = stack.value.indexOf(ent);
                    if (idx >= 0) {
                        stack.value[idx] = found;
                    }
                }
            }
        }
    } catch (e) {
        console.error(`Failed to fetch ${fetchType}:`, e);
    } finally {
        loading.value = false;
    }
};

watch(entity, (newEntity) => {
    if (newEntity) {
        const typeToFetch = props.entityType || detectEntityType(newEntity);
        if (typeToFetch) {
            fetchFullEntity(newEntity, typeToFetch);
        }
    }
}, { immediate: false });

const onKey = (e) => {
    if (e.key === 'Escape') {
        closeTooltip();
        closeAll();
    }
};

onMounted(() => {
    window.addEventListener('keydown', onKey);
    if (props.entity && props.entityType) {
        fetchFullEntity(props.entity, props.entityType);
    }
});
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));

const closeAll = () => {
    tooltip.value.entity = null;
    emit('close');
};

const isFilm = computed(() =>
    entity.value?.episode_id !== undefined
);

const exclude = [
    'id','url',
    'films','people','residents',
    'vehicles','species','starships',
    'homeworld','homeworld_id',
    'created','edited','created_at','updated_at',
    'opening_crawl'
];

const getInfo = (obj) =>
    Object.fromEntries(
        Object.entries(obj || {})
            .filter(([k,v]) =>
                !exclude.includes(k) &&
                typeof v !== 'object' &&
                v
            )
    );

const info = computed(() => getInfo(entity.value));

const normalize = (v) =>
    Array.isArray(v) ? v : v?.data ?? [];

const columns = computed(() => [
    { key:'characters', label:'Characters', items: normalize(entity.value.characters) },
    { key:'vehicles', label:'Vehicles', items: normalize(entity.value.vehicles) },
    { key:'species', label:'Species', items: normalize(entity.value.species) },
    { key:'starships', label:'Starships', items: normalize(entity.value.starships) },
].filter(c => c.items.length));

const visibleItems = (col) =>
    expanded.value[col.key]
        ? col.items
        : col.items.slice(0, LIMIT);

const toggleMore = (key) =>
    expanded.value[key] = !expanded.value[key];

const tooltip = ref({ entity:null, x:0, y:0 });

const openTooltip = (item, evt) => {
    const pad = 16;
    const w = 360;
    const h = 240;

    let x = evt.clientX + pad;
    let y = evt.clientY + pad;

    if (x + w > window.innerWidth) x = evt.clientX - w - pad;
    if (y + h > window.innerHeight) y = evt.clientY - h - pad;

    tooltip.value = { entity:item, x, y };
};

const closeTooltip = () =>
    tooltip.value.entity = null;

const drillDown = (item) => {
    closeTooltip();
    stack.value.push(item);
};

const goTo = (i) => {
    stack.value = stack.value.slice(0, i + 1);
};

const format = (k) =>
    k.replace(/_/g,' ').toUpperCase();
</script>
