# Branch protection — easeo-nl/easeo-cms

Eenmalige setup op `main`. Voorkomt dat CI-skipping releases bereiken.

## Settings

GitHub UI → Settings → Branches → Add branch protection rule:

- **Branch name pattern:** `main`
- **Require a pull request before merging:** ON
  - Required approvals: 1 (jij keurt je eigen PR goed via tweede acc of skip-if-solo via `gh pr merge --auto`)
  - Dismiss stale approvals when new commits are pushed: ON
- **Require status checks to pass before merging:** ON
  - Required checks: `PHPUnit (PHP 8.2)`, `PHPUnit (PHP 8.3)`
  - (Voeg later toe: `Integration smoke (fixture-app)` als A6 ooit unblocked is)
  - Require branches to be up to date before merging: ON
- **Require conversation resolution before merging:** ON
- **Do not allow bypassing the above settings:** ON (ook voor admins)
- **Restrict who can push to matching branches:** alleen admins (`nick-aldewereld`)

## Tags

Tag-protection (Settings → Tags → New rule):
- Pattern: `v*`
- Restrict creation tot admin

Voorkomt dat een merge per ongeluk een tag pusht; tagging blijft handmatige actie.

## Verificatie

```bash
gh api repos/easeo-nl/easeo-cms/branches/main/protection
```
Expected: JSON die laat zien dat required_status_checks bevat: ci.yml jobs (`PHPUnit (PHP 8.2)` en `PHPUnit (PHP 8.3)`).

## Test door PR te maken met failing test

```bash
git checkout -b test-branch-protection
cat > /tmp/fail.php <<'EOF'
<?php
namespace Easeo\Cms\Tests;
use PHPUnit\Framework\TestCase;
class IntentionalFailureTest extends TestCase {
    public function test_fails(): void { $this->fail("intentional"); }
}
EOF
mv /tmp/fail.php packages/cms-core/tests/IntentionalFailureTest.php
git add packages/cms-core/tests/IntentionalFailureTest.php
git commit -m "TEST: verify branch protection blocks failing PR"
git push -u origin test-branch-protection
gh pr create --base main --head test-branch-protection --title "TEST" --body "verify CI blocks"

# Probeer te mergen — moet falen
gh pr merge --merge
```
Expected: merge geblokkeerd, "Required status check expected".

Cleanup:
```bash
gh pr close test-branch-protection --delete-branch
git checkout main
git branch -D test-branch-protection
```
