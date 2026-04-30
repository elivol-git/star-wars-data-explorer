export function initHeaderAutocomplete() {
    const form = document.querySelector(".header-search")
    const input = form?.querySelector('input[name="q"]')

    if (!form || !input) return

    const list = document.createElement("div")
    list.className = "autocomplete-list"
    list.hidden = true
    form.appendChild(list)

    let controller = null
    let timer = null
    let items = []
    let activeIndex = -1

    const closeList = () => {
        list.hidden = true
        list.innerHTML = ""
        items = []
        activeIndex = -1
    }

    const render = (suggestions) => {
        items = suggestions
        activeIndex = -1

        if (!items.length) {
            closeList()
            return
        }

        list.innerHTML = items
            .map(
                (item, index) => `
                    <button type="button" class="autocomplete-item" data-index="${index}">
                        <span class="autocomplete-label">${escapeHtml(item.label)}</span>
                        <span class="autocomplete-type">${escapeHtml(item.type)}</span>
                    </button>
                `
            )
            .join("")

        list.hidden = false
    }

    const updateActiveItem = () => {
        const children = list.querySelectorAll(".autocomplete-item")
        children.forEach((el, idx) => {
            el.classList.toggle("active", idx === activeIndex)
        })
    }

    const fetchSuggestions = async (value) => {
        if (controller) controller.abort()
        controller = new AbortController()

        const res = await fetch(`/api/ai-suggestions?q=${encodeURIComponent(value)}`, {
            signal: controller.signal,
        })

        if (!res.ok) {
            closeList()
            return
        }

        const json = await res.json()
        render(Array.isArray(json) ? json : [])
    }

    input.addEventListener("input", () => {
        clearTimeout(timer)
        const value = input.value.trim()

        if (value.length < 2) {
            closeList()
            return
        }

        timer = setTimeout(() => {
            fetchSuggestions(value).catch(() => closeList())
        }, 160)
    })

    input.addEventListener("keydown", (e) => {
        if (list.hidden || !items.length) return

        if (e.key === "ArrowDown") {
            e.preventDefault()
            activeIndex = (activeIndex + 1) % items.length
            updateActiveItem()
        } else if (e.key === "ArrowUp") {
            e.preventDefault()
            activeIndex = activeIndex <= 0 ? items.length - 1 : activeIndex - 1
            updateActiveItem()
        } else if (e.key === "Enter" && activeIndex >= 0) {
            e.preventDefault()
            input.value = items[activeIndex].query
            closeList()
            form.submit()
        } else if (e.key === "Escape") {
            closeList()
        }
    })

    list.addEventListener("mousedown", (e) => {
        const target = e.target.closest(".autocomplete-item")
        if (!target) return

        const index = Number(target.dataset.index)
        const item = items[index]
        if (!item) return

        input.value = item.query
        closeList()
        form.submit()
    })

    input.addEventListener("focus", () => {
        if (items.length) {
            list.hidden = false
        }
    })

    input.addEventListener("blur", () => {
        setTimeout(closeList, 120)
    })
}

function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;")
}
