<?php
/**
 * EASEO CMS — Tracking scripts (head section)
 * Only loads if cookie consent is given (checked via JS)
 */
$gtm_id = site('tracking.gtm_id');
$ga4_id = site('tracking.google_analytics_id');
$fb_pixel = site('tracking.facebook_pixel_id');
$custom_head = site('tracking.custom_head_code');
?>
<script>
function easeoHasConsent() {
    try { return localStorage.getItem('easeo_cookies') === 'accepted'; }
    catch(e) { return false; }
}
</script>
<?php if ($gtm_id): ?>
<script>
if (easeoHasConsent()) {
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtm_id) ?>');
}
</script>
<?php endif; ?>
<?php if ($ga4_id): ?>
<script>
if (easeoHasConsent()) {
    var s = document.createElement('script');
    s.src = 'https://www.googletagmanager.com/gtag/js?id=<?= e($ga4_id) ?>';
    s.async = true;
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= e($ga4_id) ?>');
}
</script>
<?php endif; ?>
<?php if ($fb_pixel): ?>
<script>
if (easeoHasConsent()) {
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?= e($fb_pixel) ?>');
    fbq('track', 'PageView');
}
</script>
<?php endif; ?>
<?php if ($custom_head): ?>
<script>
if (easeoHasConsent()) {
    document.write(<?= json_encode($custom_head) ?>);
}
</script>
<?php endif; ?>
