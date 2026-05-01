(function () {
    if (window.__swHeaderAutocompleteInit) return;
    window.__swHeaderAutocompleteInit = true;

    function boot() {
        var form = document.getElementById('global-search-form');
        var input = document.getElementById('global-search-input');
        if (!form || !input) return;

        var list = document.createElement('div');
        list.className = 'autocomplete-list';
        list.hidden = true;
        form.appendChild(list);

        var controller = null;
        var timer = null;
        var items = [];
        var activeIndex = -1;

        function closeList() {
            list.hidden = true;
            list.innerHTML = '';
            items = [];
            activeIndex = -1;
        }

        function esc(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function render(suggestions) {
            items = Array.isArray(suggestions) ? suggestions : [];
            activeIndex = -1;

            if (!items.length) {
                closeList();
                return;
            }

            list.innerHTML = items.map(function (item, index) {
                return (
                    '<button type="button" class="autocomplete-item" data-index="' + index + '">' +
                    '<span class="autocomplete-label">' + esc(item.label) + '</span>' +
                    '<span class="autocomplete-type">' + esc(item.type) + '</span>' +
                    '</button>'
                );
            }).join('');

            list.hidden = false;
        }

        function updateActiveItem() {
            var children = list.querySelectorAll('.autocomplete-item');
            children.forEach(function (el, idx) {
                el.classList.toggle('active', idx === activeIndex);
            });
        }

        async function fetchSuggestions(value) {
            if (controller) controller.abort();
            controller = new AbortController();

            var res = await fetch('/api/ai-suggestions?q=' + encodeURIComponent(value), {
                signal: controller.signal
            });

            if (!res.ok) {
                closeList();
                return;
            }

            var json = await res.json();
            render(json);
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            var value = input.value.trim();

            if (value.length < 2) {
                closeList();
                return;
            }

            timer = setTimeout(function () {
                fetchSuggestions(value).catch(closeList);
            }, 160);
        });

        input.addEventListener('keydown', function (e) {
            if (list.hidden || !items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = (activeIndex + 1) % items.length;
                updateActiveItem();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = activeIndex <= 0 ? items.length - 1 : activeIndex - 1;
                updateActiveItem();
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                input.value = items[activeIndex].query || items[activeIndex].label || input.value;
                closeList();
                form.submit();
            } else if (e.key === 'Escape') {
                closeList();
            }
        });

        list.addEventListener('mousedown', function (e) {
            var target = e.target.closest('.autocomplete-item');
            if (!target) return;

            var index = Number(target.dataset.index);
            var item = items[index];
            if (!item) return;

            input.value = item.query || item.label || input.value;
            closeList();
            form.submit();
        });

        input.addEventListener('focus', function () {
            if (items.length) list.hidden = false;
        });

        input.addEventListener('blur', function () {
            setTimeout(closeList, 120);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
