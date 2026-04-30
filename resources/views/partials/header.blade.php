<div class="header">
    <a href="/">
        <img src="{{ asset('images/logo_gold.png') }}" class="logo" alt="Star Wars">
    </a>

    <h1 class="title">Star Wars Planets</h1>

    <form id="global-search-form" class="header-search" action="/ai-search" method="GET">
        <input
            id="global-search-input"
            type="search"
            name="q"
            placeholder="Search planets, films, starships..."
            autocomplete="off"
            value="{{ request('q') }}"
        />
    </form>

</div>

<style>
    .header-search { position: relative; }
    .autocomplete-list {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        border: 1px solid #f5d76e;
        border-radius: 10px;
        background: #0f0f0f;
        z-index: 30;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0,0,0,.35);
    }
    .autocomplete-item {
        width: 100%;
        border: 0;
        background: transparent;
        color: #f5d76e;
        text-align: left;
        padding: 8px 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        font-size: 13px;
    }
    .autocomplete-item + .autocomplete-item {
        border-top: 1px solid rgba(245, 215, 110, .2);
    }
    .autocomplete-item:hover,
    .autocomplete-item.active {
        background: rgba(245, 215, 110, .12);
    }
    .autocomplete-type {
        color: rgba(245, 215, 110, .75);
        font-size: 11px;
        text-transform: uppercase;
    }
</style>
