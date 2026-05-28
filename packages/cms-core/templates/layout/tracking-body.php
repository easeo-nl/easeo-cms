<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * EASEO CMS — Tracking scripts (body section, GTM noscript)
 */
$gtm_id = ContentRepository::siteValue('tracking.gtm_id');
$custom_body = ContentRepository::siteValue('tracking.custom_body_code');
if ($gtm_id) {
    ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php 
    echo ContentRepository::escape($gtm_id);
    ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php 
}
if ($custom_body) {
    echo $custom_body;
}
