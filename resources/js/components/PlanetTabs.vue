<template>
    <div class="planet-links">
        <!-- FILMS -->
        <div class="planet-column">
            <div class="column-title">
                Films ({{ films.length }})
            </div>

            <ul>
                <li
                    v-for="film in displayedFilms"
                    :key="film.id"
                    :class="['entity-link', { 'entity-link-match': isMatchedFilm(film) }]"
                    @click="$emit('open-entity', film)"
                >
                    {{ film.title ?? 'Unknown film' }}
                </li>
            </ul>

            <div
                v-if="films.length > LIMIT"
                class="entity-link more"
                @click="showAllFilms = !showAllFilms"
            >
                {{ showAllFilms ? 'Less' : 'More…' }}
            </div>

            <div v-if="!films.length" class="empty">No films</div>
        </div>

        <div class="planet-column">
            <div class="column-title">
                {{ people.length ? `Residents (${people.length})` : 'No residents' }}
            </div>

            <ul>
                <li
                    v-for="person in displayedPeople"
                    :key="person.id"
                    :class="['entity-link', { 'entity-link-match': isMatchedPerson(person) }]"
                    @click="$emit('open-entity', person)"
                >
                    {{ person.name ?? 'Unknown resident' }}
                </li>
            </ul>

            <div
                v-if="people.length > LIMIT"
                class="entity-link more"
                @click="showAllPeople = !showAllPeople"
            >
                {{ showAllPeople ? 'Less' : 'More…' }}
            </div>

            <div v-if="!people.length" class="empty">No residents</div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const LIMIT = 5;

const props = defineProps({
    planet: Object
});

const showAllFilms = ref(false);
const showAllPeople = ref(false);

const normalize = (v) => {
    const arr = Array.isArray(v) ? v : v?.data ?? [];
    return arr.filter(item => typeof item === 'object' && item !== null);
};

const match = computed(() => props.planet?.match ?? {});

const isSameEntity = (a, b, labelKey) => {
    if (!a || !b) return false;
    if (a.id && b.id) return a.id === b.id;
    if (labelKey && a[labelKey] && b[labelKey]) {
        return String(a[labelKey]).toLowerCase() === String(b[labelKey]).toLowerCase();
    }
    return false;
};

const prioritizeMatch = (items, matcher) => {
    const idx = items.findIndex(matcher);
    if (idx <= 0) return items;
    return [items[idx], ...items.slice(0, idx), ...items.slice(idx + 1)];
};

const films = computed(() => {
    const list = normalize(props.planet?.films);
    return prioritizeMatch(list, (film) => isSameEntity(film, match.value?.film, 'title'));
});

const people = computed(() => {
    const list = normalize(props.planet?.people);
    return prioritizeMatch(list, (person) => isSameEntity(person, match.value?.person, 'name'));
});

const isMatchedFilm = (film) => isSameEntity(film, match.value?.film, 'title');
const isMatchedPerson = (person) => isSameEntity(person, match.value?.person, 'name');

const displayedFilms = computed(() =>
    showAllFilms.value ? films.value : films.value.slice(0, LIMIT)
);

const displayedPeople = computed(() =>
    showAllPeople.value ? people.value : people.value.slice(0, LIMIT)
);
</script>

<style scoped>
.entity-link-match {
    color: #ffd700;
    font-weight: 700;
}
</style>
