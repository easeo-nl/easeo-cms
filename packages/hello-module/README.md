# easeo/hello-module

Reference implementation of an [easeo/cms-core](https://github.com/easeo-nl/cms-core) module.

## What it does

- Registers `GET /hello` → renders "Hallo wereld" HTML via the module-router entry.
- Adds a `hello` beheer-tab (admin panel page) with a "module werkt" status indicator.
- Subscribes to the `cms.footer.before` hook, emitting `<!-- hello-module footer -->`.

## Installation (production)

```bash
composer require easeo/hello-module
```

Then add `"easeo/hello-module"` to `data/site.config.json`:

```json
{
    "modules": ["easeo/hello-module"]
}
```

## Monorepo dev

In the monorepo, `apps/easeo-website/composer.json` references this package as a path repository:

```json
{
    "repositories": [
        { "type": "path", "url": "../../packages/*", "options": {"symlink": true} }
    ],
    "require": {
        "easeo/hello-module": "*"
    }
}
```

## Writing your own module

See `src/Module.php` — implement `Easeo\Cms\Module\ModuleInterface` and declare the class in your `composer.json`:

```json
{
    "extra": {
        "easeo": {
            "module-class": "Your\\Namespace\\Module"
        }
    }
}
```

Refer to the [cms-core docs](../../packages/cms-core/) for the full Registry API.
