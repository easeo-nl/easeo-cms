<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Legal\LegalPages;
/**
 * EASEO CMS — Cookie policy page
 */
require_once __DIR__ . '/../vendor/autoload.php';
ContentRepository::checkSetup();
$pageTitle = 'Cookiebeleid | ' . ContentRepository::siteValue('company.name', 'EASEO');
$metaDescription = 'Cookiebeleid van ' . ContentRepository::siteValue('company.name', 'ons bedrijf');
require_once EASEO_CORE . '/src/legacy/header.php';
?>

<section class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        <div class="content-area">
            <?php 
echo nl2br(ContentRepository::escape(LegalPages::getText('cookies')));
?>
        </div>
    </div>
</section>

<?php 
require_once EASEO_CORE . '/src/legacy/footer.php';
