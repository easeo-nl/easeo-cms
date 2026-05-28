<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * EASEO CMS — Tracking scripts (head section)
 * Only loads if cookie consent is given (checked via JS)
 */
$gtm_id = ContentRepository::siteValue('tracking.gtm_id');
$ga4_id = ContentRepository::siteValue('tracking.google_analytics_id');
$fb_pixel = ContentRepository::siteValue('tracking.facebook_pixel_id');
$custom_head = ContentRepository::siteValue('tracking.custom_head_code');
?>
<script>
function easeoHasConsent() {
    try { return localStorage.getItem('easeo_cookies') === 'accepted'; }
    catch(e) { return false; }
}
</script>
<?php 
if ($gtm_id) {
    ?>
<script>
if (easeoHasConsent()) {
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php 
    echo ContentRepository::escape($gtm_id);
    ?>');
}
</script>
<?php 
}
if ($ga4_id) {
    ?>
<script>
if (easeoHasConsent()) {
    var s = document.createElement('script');
    s.src = 'https://www.googletagmanager.com/gtag/js?id=<?php 
    echo ContentRepository::escape($ga4_id);
    ?>';
    s.async = true;
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?php 
    echo ContentRepository::escape($ga4_id);
    ?>');
}
</script>
<?php 
}
if ($fb_pixel) {
    ?>
<script>
if (easeoHasConsent()) {
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?php 
    echo ContentRepository::escape($fb_pixel);
    ?>');
    fbq('track', 'PageView');
}
</script>
<?php 
}
if ($custom_head) {
    ?>
<script>
if (easeoHasConsent()) {
    var div = document.createElement('div');
    div.innerHTML = <?php 
    echo json_encode($custom_head);
    ?>;
    Array.from(div.childNodes).forEach(function(n) { document.head.appendChild(n.cloneNode(true)); });
}
</script>
<?php 
}
