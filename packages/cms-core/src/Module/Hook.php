<?php
declare(strict_types=1);

namespace Easeo\Cms\Module;

/**
 * Named hook dispatcher. Subscribers register callable handlers that
 * are invoked when fire() is called. Their return strings are
 * concatenated into the output (typical use: rendering extra HTML at
 * footer/header injection points).
 *
 * Failure-mode: a throwing subscriber is logged via error_log() and
 * the dispatcher continues with the next subscriber (fail-soft).
 * Use a strict dispatcher subclass if you need fail-fast semantics.
 *
 * Hook names use dot.notation: 'cms.footer.before', 'cms.head.end',
 * 'cms.admin.dashboard.widgets', etc.
 */
final class Hook
{
    /** @var array<string,list<array{handler:callable,owner:string,priority:int}>> */
    private array $subscribers = [];

    public function subscribe(string $hookName, callable $handler, string $owner, int $priority = 100): void
    {
        $this->subscribers[$hookName] ??= [];
        $this->subscribers[$hookName][] = [
            'handler'  => $handler,
            'owner'    => $owner,
            'priority' => $priority,
        ];
    }

    /**
     * Invoke all subscribers for $hookName, passing $context to each.
     * Each subscriber's string return is concatenated into the output.
     * Non-string returns are coerced (null → '', other → strval).
     *
     * Subscribers are invoked in priority order (lower first). Within
     * the same priority, registration-order is preserved.
     *
     * @param array<string,mixed> $context
     */
    public function fire(string $hookName, array $context = []): string
    {
        $subs = $this->subscribers[$hookName] ?? [];
        if (empty($subs)) {
            return '';
        }

        // Stable sort by priority
        usort($subs, fn($a, $b) => $a['priority'] <=> $b['priority']);

        $output = '';
        foreach ($subs as $sub) {
            try {
                $result = ($sub['handler'])($context);
                if ($result === null) {
                    continue;
                }
                $output .= is_string($result) ? $result : (string) $result;
            } catch (\Throwable $e) {
                error_log(sprintf(
                    '[easeo/cms-core Hook] subscriber from %s on %s threw: %s',
                    $sub['owner'],
                    $hookName,
                    $e->getMessage()
                ));
                // fail-soft: continue with next subscriber
            }
        }
        return $output;
    }

    public function hasSubscribers(string $hookName): bool
    {
        return !empty($this->subscribers[$hookName]);
    }

    /**
     * @return list<string>
     */
    public function knownHooks(): array
    {
        return array_keys($this->subscribers);
    }
}
