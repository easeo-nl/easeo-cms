# easeo/cms-core migrations

This directory holds forward-only migrations that run at first request after
a klant-site upgrades the `easeo/cms-core` Composer package.

## File-name convention

`NNNN_descriptive_name.php` — four-digit zero-padded version, e.g.
`0042_add_meta_to_pages.php`. The runner sorts by version (returned by
`version()` method, not filename), but lexical filename order should match
for human-readability.

## Format

Each file returns an anonymous class implementing `MigrationInterface`:

```php
<?php
declare(strict_types=1);

use Easeo\Cms\Migration\MigrationInterface;

return new class implements MigrationInterface {
    public function version(): int { return 42; }
    public function description(): string { return 'Add meta.canonical to pages'; }

    public function up(string $dataDir): void
    {
        $file = "$dataDir/pages.json";
        if (!is_file($file)) {
            return;
        }
        $pages = json_decode(file_get_contents($file), true) ?? [];
        foreach ($pages as &$p) {
            $p['meta']['canonical'] = $p['meta']['canonical'] ?? '';
        }
        $tmp = "$file.tmp";
        file_put_contents($tmp, json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        rename($tmp, $file); // atomic
    }
};
```

## Constraints

- **Forward-only** — no `down()`. Rolling back means restoring a backup.
- **Idempotent** — running the same migration twice must be safe (use
  `array_key_exists` / `?? default` patterns).
- **No external services** — no HTTP, no database, no external SMTP. Read
  and write files in `$dataDir` only.
- **Atomic writes** — always write to `$tmp` then `rename($tmp, $real)`
  so partial writes don't corrupt state.
- **No exceptions on missing optional files** — `data/pages.json` may not
  exist on a fresh install (Bootstrapper runs first); migrations should
  no-op gracefully.

The runner records the highest applied version in `data/.schema-version`
and uses `data/.migration.lock` (flock LOCK_EX) to serialize concurrent
requests.
