<?php
declare(strict_types=1);

namespace Easeo\Cms\Module;

/**
 * Aggregator that modules call into during register(). Holds:
 * - a RouteTable for module-defined HTTP routes
 * - a BeheerPages collection for admin-menu entries
 * - a Hook dispatcher for event subscriptions
 * - a config-schema map (key → optional default) — used by future
 *   batch to validate site.config.json against module expectations
 *
 * Modules typically call:
 *
 *   public function register(Registry $r): void
 *   {
 *       $r->route('/hello', 'GET', fn() => 'Hallo wereld', $this->name());
 *       $r->beheerPage('hello', 'Hello', '<svg path>', $this->renderAdmin(...), $this->name());
 *       $r->hook('cms.footer.before', fn() => '<!-- hello-module footer -->', $this->name());
 *       $r->configSchema('hello.show_greeting', true, $this->name());
 *   }
 */
final class Registry
{
    private RouteTable $routes;
    private BeheerPages $beheerPages;
    private Hook $hooks;

    /** @var array<string,array{default:mixed,owner:string}> */
    private array $configSchema = [];

    public function __construct()
    {
        $this->routes = new RouteTable();
        $this->beheerPages = new BeheerPages();
        $this->hooks = new Hook();
    }

    public function route(string $pattern, string $method, callable $handler, string $owner): void
    {
        $this->routes->add($pattern, $method, $handler, $owner);
    }

    public function beheerPage(
        string $tab,
        string $label,
        string $icon,
        callable $renderer,
        string $owner,
        bool $adminOnly = false
    ): void {
        $this->beheerPages->add($tab, $label, $icon, $renderer, $owner, $adminOnly);
    }

    public function hook(string $hookName, callable $handler, string $owner, int $priority = 100): void
    {
        $this->hooks->subscribe($hookName, $handler, $owner, $priority);
    }

    public function configSchema(string $dotKey, mixed $default, string $owner): void
    {
        $this->configSchema[$dotKey] = ['default' => $default, 'owner' => $owner];
    }

    public function routes(): RouteTable
    {
        return $this->routes;
    }

    public function beheerPages(): BeheerPages
    {
        return $this->beheerPages;
    }

    public function hooks(): Hook
    {
        return $this->hooks;
    }

    /**
     * @return array<string,array{default:mixed,owner:string}>
     */
    public function configSchemaAll(): array
    {
        return $this->configSchema;
    }
}
