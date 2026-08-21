{{-- Cerez bildirimi. Sunucuya kayit tutmaz; tercih tarayicida saklanir.
     Analitik betigi yalnizca onay verilirse yuklenir (KVKK/GDPR). --}}
<div id="cookie-banner" class="position-fixed bottom-0 start-0 end-0 p-3" style="z-index:1080; display:none;">
    <div class="panel shadow d-flex flex-wrap align-items-center justify-content-between gap-3 mx-auto"
         style="max-width:900px;">
        <p class="small mb-0 text-muted-2" style="max-width:60ch;">
            {{ __('site.cookie.text') }}
            <a href="{{ lroute('pages.show', 'cerez-politikasi') }}">{{ __('site.cookie.policy') }}</a>
        </p>
        <div class="d-flex gap-2 flex-shrink-0">
            <button type="button" class="btn btn-outline-sea btn-sm" data-cookie="reject">
                {{ __('site.cookie.reject') }}
            </button>
            <button type="button" class="btn btn-brass btn-sm" data-cookie="accept">
                {{ __('site.cookie.accept') }}
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var KEY = 'cookie-consent';
    var banner = document.getElementById('cookie-banner');
    var choice = null;

    try { choice = localStorage.getItem(KEY); } catch (e) { choice = 'reject'; }

    if (!choice) {
        banner.style.display = 'block';
    } else if (choice === 'accept') {
        loadAnalytics();
    }

    banner.querySelectorAll('[data-cookie]').forEach(function (button) {
        button.addEventListener('click', function () {
            var value = this.dataset.cookie;
            try { localStorage.setItem(KEY, value); } catch (e) {}
            banner.style.display = 'none';
            if (value === 'accept') { loadAnalytics(); }
        });
    });

    // Olcum kimligi yoksa yukleyici hic basilmaz: olu kod ve gereksiz ucuncu
    // taraf adresi sayfada durmasin.
    function loadAnalytics() {
@if (setting('google_analytics_id'))
        if (window.__gaLoaded) { return; }
        window.__gaLoaded = true;

        var id = @json(setting('google_analytics_id'));
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + id;
        document.head.appendChild(s);

        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        window.gtag = gtag;
        gtag('js', new Date());
        gtag('config', id, { anonymize_ip: true });
@endif
    }
})();
</script>
