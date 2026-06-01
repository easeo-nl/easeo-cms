# Migrations

## Wat zijn migraties?

Migraties zijn versioned scripts die de `data/`-directory naar een nieuwe staat brengen.
Denk aan: aanmaken van SQLite-tabellen, toevoegen van config-bestanden, omzetten van JSON-structuren.

Ze draaien **automatisch** bij `App::boot()` als er nieuwe versies zijn.

## Hoe het werkt

```
App::boot()
    |
    v
Migration\Runner::runPending()
    |
    v
Vergelijk .schema-version met beschikbare migrations/
    |
    v
Voor elke pending migration: $m->up($dataDir)
    |
    v
.schema-version bijwerken (atomic write)
    |
    v
AuditLogger schrijft "migration_applied" naar data/audit.log
```

## Een migratie schrijven

Maak `packages/cms-core/migrations/<NNNN>_beschrijving.php`:

```php
<?php
use Easeo\Cms\Migration\MigrationInterface;

return new class implements MigrationInterface {
    public function version(): int     { return 2; }
    public function description(): string { return 'Voeg settings-tabel toe'; }
    public function up(string $dataDir): void
    {
        $db = new SQLite3($dataDir . '/cms.db');
        $db->exec('CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT)');
    }
};
```

## Regels

- Versienummer = bestandsprefix `NNNN_` — altijd 4 cijfers, opeenvolgend.
- Migrations zijn **idempotent**: draaien een tweede keer mag geen fout geven.
- Nooit data verwijderen in een migratie — gebruik een aparte cleanup-migratie.
- Geen `down()` methode — rollback via `bin/easeo-doctor` smoke + handmatige herstel.

## Schema-versie bekijken

```bash
cat data/.schema-version          # huidig schema-versienummer
./bin/easeo-doctor                # toont schema-versie als onderdeel van diagnostiek
```

## Troubleshooting

- **Lock file blijft staan**: Verwijder `data/.migration.lock` als een deploy halverwege aborteerde.
- **Migratie faalt**: Zie `data/audit.log` voor de foutmelding + `error_log` van PHP-FPM.
