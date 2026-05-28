## Wat verandert er?

<korte beschrijving>

## Checklist

- [ ] Lokaal getest (`php -S localhost:8080 -t public/`)
- [ ] CI groen op deze PR
- [ ] Geen `data/*.json` per ongeluk gestaged (`git status` check, pr-check workflow vangt 'm ook af)
- [ ] Bij CSS/template change: verifieer in browser dat homepage nog goed rendert
- [ ] Bij twijfel: ping @{{BACKSTOP_HANDLE}}

## Smoke-test post-merge

CI runt automatisch na deploy:
- Homepage HTTP 200
- GTM-script aanwezig in HTML
- `/sitemap.xml` bereikbaar

Als dat faalt, rolt de site automatisch terug (atomic symlink swap behoudt vorige release).
