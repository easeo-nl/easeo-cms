<?php
/**
 * EASEO CMS — Beheer (admin) entry-point for easeo-website.
 *
 * Boots App (so App::current() is populated and modules are registered),
 * then hands off to the cms-core beheer/index.php router.
 *
 * In production (Apache on Hostinger): the .htaccess rewrite for ^beheer/
 * can point here instead of the old direct include, to ensure modules are
 * loaded before the admin UI runs. The existing .htaccess already passes
 * /beheer/ through as-is (no rewrite rule for it), so in the PHP dev-router
 * we route /beheer/* here explicitly.
 *
 * Usage (php -S):
 *   Handled automatically by router.php which routes /beheer/ to this file.
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Boot App so modules register their routes + beheer-pages before the admin UI renders.
\Easeo\Cms\App::boot(__DIR__ . '/..');

// Hand off to cms-core beheer router.
require __DIR__ . '/../../packages/cms-core/beheer/index.php';
