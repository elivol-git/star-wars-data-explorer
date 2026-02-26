// app.js
import { createApp } from "vue"
import Planets from "./components/Planets.vue"
import AiSearchPage from "./components/AiSearchPage.vue"
import "../css/ai-search.css"

const el = document.getElementById("app")

if (el) {
    // If planets exist -> mount Planets page
    if (el.dataset.planets) {
        let planetsData = []

        try {
            const parsed = JSON.parse(el.dataset.planets)
            planetsData = parsed.data || parsed.items || parsed || []
        } catch (e) {
            console.error("Invalid JSON in data-planets:", e)
        }

        createApp(Planets, { planets: planetsData }).mount("#app")
    }
    // Otherwise -> mount AI search page
    else {
        createApp(AiSearchPage).mount("#app")
    }
}
