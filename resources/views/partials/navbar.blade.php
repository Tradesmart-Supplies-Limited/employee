<nav class="navbar admin-navbar navbar-expand bg-white">

    <div class="container-fluid px-3 px-lg-4">

        <button class="sidebar-toggle" type="button" data-sidebar-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>


        <div class="navbar-actions ms-auto d-flex align-items-center gap-2">

            {{-- Colour theme + light / dark / device --}}
            @auth
            @php
                $tpCurrent = \App\Support\Themes::valid(auth()->user()->ui_accent);
            @endphp
            <div class="dropdown">
                <button class="icon-button" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Colour theme" title="Colour theme">
                    <i class="bi bi-palette"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end theme-menu shadow" id="themeMenu" data-save-url="{{ route('account.theme') }}">
                    <h6>Appearance</h6>
                    <div class="theme-modes" role="group" aria-label="Light or dark">
                        <button type="button" data-mode="light" aria-pressed="false"><i class="bi bi-sun"></i>Light</button>
                        <button type="button" data-mode="dark" aria-pressed="false"><i class="bi bi-moon-stars"></i>Dark</button>
                        <button type="button" data-mode="device" aria-pressed="false"><i class="bi bi-laptop"></i>Device</button>
                    </div>
                    <h6>Colour</h6>
                    <div class="theme-swatches" role="group" aria-label="Colour theme">
                        @foreach(\App\Support\Themes::ALL as $key => [$name, $light, $dark, $desc])
                            <button type="button" class="theme-swatch" data-accent="{{ $key }}" aria-pressed="{{ $key === $tpCurrent ? 'true' : 'false' }}" title="{{ $desc }}" style="--sw: {{ $light }}; --sw2: {{ $dark }}">
                                <span class="dot"></span>{{ $name }}
                            </button>
                        @endforeach
                    </div>
                    <p class="theme-note" id="themeNote">Colour is saved to your account. Light or dark is saved on this device.</p>
                </div>
            </div>
            @endauth

            {{-- Theme Toggle --}}
            <button class="icon-button" type="button" data-theme-toggle>
                <i class="bi bi-moon-stars" data-theme-icon></i>
            </button>

        </div>

    </div>

</nav>

@auth
<script>
(function () {
    var menu = document.getElementById('themeMenu');
    if (!menu) return;
    var root = document.documentElement, KEY = 'adminHMD.colorTheme';
    var swatches = [].slice.call(menu.querySelectorAll('[data-accent]'));
    var modes = [].slice.call(menu.querySelectorAll('[data-mode]'));
    var note = document.getElementById('themeNote');

    function savedMode() { try { var m = localStorage.getItem(KEY); return m === 'dark' || m === 'light' ? m : 'device'; } catch (e) { return 'device'; } }
    function deviceDark() { return window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches; }
    function paintMode(mode) {
        var t = mode === 'device' ? (deviceDark() ? 'dark' : 'light') : mode;
        root.setAttribute('data-theme', t); root.setAttribute('data-bs-theme', t);
        [].forEach.call(document.querySelectorAll('[data-theme-icon]'), function (i) { i.className = t === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars'; });
        modes.forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.mode === mode ? 'true' : 'false'); });
    }
    paintMode(savedMode());

    modes.forEach(function (b) {
        b.addEventListener('click', function () {
            try { b.dataset.mode === 'device' ? localStorage.removeItem(KEY) : localStorage.setItem(KEY, b.dataset.mode); } catch (e) {}
            paintMode(b.dataset.mode);
        });
    });
    // The moon/sun button in the bar sets an explicit mode: reflect it here.
    [].forEach.call(document.querySelectorAll('[data-theme-toggle]'), function (t) {
        t.addEventListener('click', function () { setTimeout(function () { paintMode(savedMode()); }, 0); });
    });
    if (window.matchMedia) {
        try { matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () { if (savedMode() === 'device') paintMode('device'); }); } catch (e) {}
    }

    swatches.forEach(function (b) {
        b.addEventListener('click', function () {
            var before = root.getAttribute('data-accent'), accent = b.dataset.accent;
            root.setAttribute('data-accent', accent);
            swatches.forEach(function (s) { s.setAttribute('aria-pressed', s === b ? 'true' : 'false'); });
            var token = (document.querySelector('meta[name="csrf-token"]') || {}).content;
            fetch(menu.dataset.saveUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ accent: accent })
            }).then(function (r) {
                if (!r.ok) throw new Error(r.status);
                note.textContent = b.textContent.trim() + ' saved. It follows you on every device.';
            }).catch(function () {
                root.setAttribute('data-accent', before);
                swatches.forEach(function (s) { s.setAttribute('aria-pressed', s.dataset.accent === before ? 'true' : 'false'); });
                note.textContent = 'Couldn\'t save the colour. Check your connection and try again.';
            });
        });
    });
})();
</script>
@endauth
