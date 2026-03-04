<?php
/**
 * EASEO CMS — AVG/GDPR Cookie Consent Banner
 * Uses localStorage key: easeo_cookies
 */
?>
<div id="cookie-banner" class="fixed bottom-0 left-0 right-0 bg-dark text-white p-4 z-[9999] shadow-lg transform translate-y-full transition-transform duration-300" style="display:none;">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-sm flex-1">
            Wij gebruiken cookies om uw ervaring te verbeteren. Door deze site te gebruiken gaat u akkoord met ons
            <a href="/cookiebeleid" class="underline hover:text-primary">cookiebeleid</a>.
        </div>
        <div class="flex gap-3 shrink-0">
            <button onclick="easeoDeclineCookies()" class="px-4 py-2 text-sm border border-gray-500 rounded hover:bg-gray-700 transition-colors">
                Weigeren
            </button>
            <button onclick="easeoAcceptCookies()" class="px-4 py-2 text-sm bg-primary text-white rounded hover:opacity-90 transition-colors">
                Accepteren
            </button>
        </div>
    </div>
</div>
<script>
(function() {
    var banner = document.getElementById('cookie-banner');
    try {
        var consent = localStorage.getItem('easeo_cookies');
        if (!consent) {
            banner.style.display = 'block';
            setTimeout(function() { banner.classList.remove('translate-y-full'); }, 100);
        }
    } catch(e) {}
})();

function easeoAcceptCookies() {
    try { localStorage.setItem('easeo_cookies', 'accepted'); } catch(e) {}
    closeBanner();
    location.reload();
}

function easeoDeclineCookies() {
    try { localStorage.setItem('easeo_cookies', 'declined'); } catch(e) {}
    closeBanner();
}

function closeBanner() {
    var banner = document.getElementById('cookie-banner');
    banner.classList.add('translate-y-full');
    setTimeout(function() { banner.style.display = 'none'; }, 300);
}
</script>
