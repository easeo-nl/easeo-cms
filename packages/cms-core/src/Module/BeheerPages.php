<?php
declare(strict_types=1);

namespace Easeo\Cms\Module;

/**
 * Internal collection of admin-menu entries registered by modules.
 * Each entry has a tab-slug (URL fragment), label, icon (SVG path-d),
 * a renderer callable, and ownership metadata.
 *
 * Duplicate tab-slugs raise DuplicateBeheerPageException.
 *
 * The eventual beheer-nav integration (later batch) merges this with
 * the core admin pages such that core pages win.
 */
final class BeheerPages
{
    /** @var array<string,array{tab:string,label:string,icon:string,renderer:callable,owner:string,admin_only:bool}> */
    private array $pages = [];

    public function add(
        string $tab,
        string $label,
        string $icon,
        callable $renderer,
        string $owner,
        bool $adminOnly = false
    ): void {
        if (!preg_match('/^[a-z0-9-]+$/', $tab)) {
            throw new \InvalidArgumentException("Beheer-page tab '$tab' must be lowercase kebab-case (matched /^[a-z0-9-]+$/)");
        }
        if (isset($this->pages[$tab])) {
            $existingOwner = $this->pages[$tab]['owner'];
            throw new DuplicateBeheerPageException(
                "Beheer-page tab '$tab' is already registered by module '$existingOwner' (now being registered by '$owner')"
            );
        }
        $this->pages[$tab] = [
            'tab'        => $tab,
            'label'      => $label,
            'icon'       => $icon,
            'renderer'   => $renderer,
            'owner'      => $owner,
            'admin_only' => $adminOnly,
        ];
    }

    /**
     * @return array<string,array{tab:string,label:string,icon:string,renderer:callable,owner:string,admin_only:bool}>
     */
    public function all(): array
    {
        return $this->pages;
    }

    public function get(string $tab): ?array
    {
        return $this->pages[$tab] ?? null;
    }
}
