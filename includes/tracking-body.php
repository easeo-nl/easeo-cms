<?php
/**
 * EASEO CMS — Tracking scripts (body section, GTM noscript)
 */
$gtm_id = site('tracking.gtm_id');
$custom_body = site('tracking.custom_body_code');
?>
<?php if ($gtm_id): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($gtm_id) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>
<?php if ($custom_body): ?>
<?= $custom_body ?>
<?php endif; ?>
