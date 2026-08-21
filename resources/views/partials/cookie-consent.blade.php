{{-- Cerez bildirimi. Tercih tarayicida saklanir, sunucuya kayit tutulmaz.
     Analitik betigi yalnizca olcum kimligi tanimliysa basilir ve yalnizca
     onay verilirse yuklenir (KVKK/GDPR). --}}
<div id="cookie-banner" class="fixed inset-x-0 bottom-0 z-50 hidden p-3 sm:p-4">
    <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-4 rounded-xl border border-sea-200 bg-white p-4 shadow-2xl shadow-sea-900/15">
        <p class="max-w-xl text-sm text-sea-600">
            {{ __('site.cookie.text') }}
            <a href="{{ lroute('pages.show', 'cerez-politikasi') }}" class="text-brass-700 underline">{{ __('site.cookie.policy') }}</a>
        </p>
        <div class="flex shrink-0 gap-2">
            <button type="button" class="btn btn-ghost btn-sm" data-cookie="reject">{{ __('site.cookie.reject') }}</button>
            <button type="button" class="btn btn-brass btn-sm" data-cookie="accept">{{ __('site.cookie.accept') }}</button>
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
        banner.classList.remove('hidden');
    } else if (choice === 'accept') {
        loadAnalytics();
    }

    banner.querySelectorAll('[data-cookie]').forEach(function (button) {
        button.addEventListener('click', function () {
            var value = this.dataset.cookie;
            try { localStorage.setItem(KEY, value); } catch (e) {}
            banner.classList.add('hidden');
            if (value === 'accept') { loadAnalytics(); }
        });
    });

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
