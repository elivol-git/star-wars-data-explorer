<div class="header">
    <a href="/">
        <img src="{{ asset('images/logo_gold.png') }}" class="logo" alt="Star Wars">
    </a>

    <h1 class="title">Star Wars Planets</h1>

    <form class="header-search" action="/ai-search" method="GET">
        <input
            type="search"
            name="q"
            placeholder="Search planets, films, starships..."
            autocomplete="off"
            value="{{ request('q') }}"
        />
    </form>

</div>
