# Sylius Redirect Plugin — Sylius 2.x Upgrade Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Upgrade setono/sylius-redirect-plugin from Sylius 1.x to Sylius 2.x compatibility, starting from upstream v2.7.0.

**Architecture:** The plugin provides HTTP redirect management — entity, admin CRUD, kernel event listeners. The upgrade requires: dependency bumps, plugin class modernisation, Doctrine XML-to-attribute mapping, config/template directory restructuring, SemanticUI-to-Bootstrap template conversion, and test infrastructure replacement. No fundamental architectural changes needed — the core logic (event subscribers, resolvers, validators) is Symfony-native and needs minimal modification.

**Tech Stack:** PHP 8.2+, Symfony 6.4/7.x, Sylius 2.x, Doctrine ORM 2.7/3.x, league/uri 6/7

**Branch:** `sylius-2.x` (already created from upstream `v2.7.0` tag)

---

### Task 1: Update composer.json for Sylius 2.x

**Files:**
- Modify: `composer.json`

**Step 1: Update composer.json**

Replace the full contents of `composer.json`:

```json
{
    "name": "setono/sylius-redirect-plugin",
    "description": "Sylius plugin for managing redirects",
    "license": "MIT",
    "type": "sylius-plugin",
    "keywords": [
        "redirect",
        "setono",
        "sylius",
        "sylius-plugin"
    ],
    "require": {
        "php": ">=8.2",
        "doctrine/collections": "^2.2",
        "doctrine/orm": "^2.7 || ^3.0",
        "doctrine/persistence": "^3.0",
        "league/uri": "^6.0 || ^7.5",
        "league/uri-components": "^2.3 || ^7.5",
        "psr/log": "^2.0 || ^3.0",
        "sylius/channel-bundle": "^2.0",
        "sylius/core-bundle": "^2.0",
        "sylius/grid-bundle": "^2.0",
        "sylius/product-bundle": "^2.0",
        "sylius/resource-bundle": "^2.0",
        "sylius/taxonomy-bundle": "^2.0",
        "sylius/ui-bundle": "^2.0",
        "symfony/config": "^6.4 || ^7.1",
        "symfony/console": "^6.4 || ^7.1",
        "symfony/dependency-injection": "^6.4 || ^7.1",
        "symfony/event-dispatcher": "^6.4 || ^7.1",
        "symfony/form": "^6.4 || ^7.1",
        "symfony/http-foundation": "^6.4 || ^7.1",
        "symfony/http-kernel": "^6.4 || ^7.1",
        "symfony/routing": "^6.4 || ^7.1",
        "symfony/validator": "^6.4 || ^7.1",
        "webmozart/assert": "^1.11"
    },
    "require-dev": {
        "phpspec/phpspec": "^7.3",
        "phpunit/phpunit": "^10.5 || ^11.0",
        "sylius/sylius": "~2.0.0",
        "sylius/test-application": "^2.0",
        "symfony/debug-bundle": "^6.4 || ^7.1",
        "symfony/dotenv": "^6.4 || ^7.1",
        "symfony/intl": "^6.4 || ^7.1",
        "symfony/web-profiler-bundle": "^6.4 || ^7.1",
        "symfony/webpack-encore-bundle": "^2.2"
    },
    "prefer-stable": true,
    "autoload": {
        "psr-4": {
            "Setono\\SyliusRedirectPlugin\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\Setono\\SyliusRedirectPlugin\\": "tests/"
        }
    },
    "config": {
        "allow-plugins": {
            "symfony/thanks": false
        },
        "sort-packages": true
    }
}
```

Key changes:
- PHP `>=8.2`
- All `sylius/*` packages to `^2.0`
- All `symfony/*` packages to `^6.4 || ^7.1`
- `doctrine/collections` to `^2.2`, `doctrine/persistence` to `^3.0`
- Removed `sylius/channel` (merged into channel-bundle in Sylius 2)
- Removed psalm, phpspec prophecy, setono code quality packs, api-platform (not needed for plugin itself)
- Removed `autoload-dev.classmap` (test application will use `sylius/test-application`)
- Added `sylius/test-application: ^2.0` and `sylius/grid-bundle: ^2.0`

**Step 2: Commit**

```bash
git add composer.json
git commit -m "Update composer.json for Sylius 2.x compatibility"
```

---

### Task 2: Modernise plugin bundle class

**Files:**
- Modify: `src/SetonoSyliusRedirectPlugin.php`

**Step 1: Replace plugin class**

The Sylius 2.x plugin class no longer uses `SyliusPluginTrait` or extends `AbstractResourceBundle`. It extends `AbstractBundle` (Symfony 6.4+) and provides `getPath()`.

```php
<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SetonoSyliusRedirectPlugin extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
```

Note: The resource registration that `AbstractResourceBundle` previously handled is now done via the `Configuration.php` and `Extension` class with `registerResources()` — which this plugin already does explicitly in `SetonoSyliusRedirectExtension::load()`. So removing `AbstractResourceBundle` is safe; the extension handles it.

**Step 2: Commit**

```bash
git add src/SetonoSyliusRedirectPlugin.php
git commit -m "Modernise plugin class: remove SyliusPluginTrait, extend AbstractBundle"
```

---

### Task 3: Move config, translations, and templates to root directories

**Files:**
- Move: `src/Resources/config/` → `config/`
- Move: `src/Resources/translations/` → `translations/`
- Move: `src/Resources/views/` → `templates/`
- Move: `src/Resources/public/` → `public/`
- Modify: `src/DependencyInjection/SetonoSyliusRedirectExtension.php` (update FileLocator path)
- Delete: `src/Resources/` directory (after moves)

**Step 1: Move directories**

```bash
# From project root
mv src/Resources/config config
mv src/Resources/translations translations
mv src/Resources/views templates
mv src/Resources/public public
rm -rf src/Resources
```

**Step 2: Update the Extension class FileLocator path**

In `src/DependencyInjection/SetonoSyliusRedirectExtension.php`, change:

```php
$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
```

To:

```php
$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
```

**Step 3: Update internal config import paths**

In `config/app/config.yaml`, change:

```yaml
imports:
- { resource: "@SetonoSyliusRedirectPlugin/config/grids.yaml" }
```

In `config/grids.yaml`, change:

```yaml
imports:
- { resource: '@SetonoSyliusRedirectPlugin/config/grids/setono_sylius_redirect_admin_redirect.yaml' }
```

In `config/admin_routing.yaml`, update template references:

```yaml
setono_sylius_redirect_admin_redirect:
    resource: |
        alias: setono_sylius_redirect.redirect
        section: admin
        permission: true
        templates: "@SyliusAdmin\\Crud"
        redirect: update
        grid: setono_sylius_redirect_admin_redirect
        vars:
            all:
                subheader: setono_sylius_redirect.ui.manage_redirects
                templates:
                    form: "@SetonoSyliusRedirectPlugin/templates/Admin/Redirect/_form.html.twig"
            index:
                icon: 'chart bar'
    type: sylius.resource
```

Wait — the `@SetonoSyliusRedirectPlugin` prefix resolves via `getPath()` which now returns the project root. So `@SetonoSyliusRedirectPlugin/templates/...` is correct for templates. But the old reference `@SetonoSyliusRedirectPlugin/Admin/Redirect/_form.html.twig` used the `views/` directory implicitly via Symfony's bundle convention. With the new structure, Symfony will look for templates in `templates/` under `getPath()` automatically.

Actually, re-check: Symfony's AbstractBundle automatically registers `templates/` as the template directory. So the Twig namespace `@SetonoSyliusRedirectPlugin` maps to `<getPath()>/templates/`. Therefore the form reference should stay as:

```yaml
form: "@SetonoSyliusRedirectPlugin/Admin/Redirect/_form.html.twig"
```

This will resolve to `<root>/templates/Admin/Redirect/_form.html.twig` — correct.

**Step 4: Update block event listener template references**

In `config/services/block_event_listener.xml`, the `@SetonoSyliusRedirectPlugin/Admin/_javascripts.html.twig` reference will now correctly resolve to `templates/Admin/_javascripts.html.twig` via the Twig namespace. No change needed here.

**Step 5: Commit**

```bash
git add -A
git commit -m "Move config, translations, templates, and public assets to root directories"
```

---

### Task 4: Convert Doctrine XML mapping to PHP attributes

**Files:**
- Modify: `src/Model/Redirect.php` (add ORM attributes)
- Delete: `config/doctrine/model/Redirect.orm.xml`

**Step 1: Add PHP 8 attribute mapping to the entity**

Replace `src/Model/Redirect.php`:

```php
<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\ToggleableTrait;

#[ORM\MappedSuperclass]
#[ORM\Table(name: 'setono_sylius_redirect__redirect')]
#[ORM\Index(columns: ['last_accessed'])]
#[ORM\Index(columns: ['enabled'])]
#[ORM\Index(columns: ['only_404'])]
#[ORM\Index(name: 'findOneEnabledBySource_idx', columns: ['source', 'enabled'])]
#[ORM\Index(name: 'findOne404EnabledBySource_idx', columns: ['source', 'enabled', 'only_404'])]
class Redirect implements RedirectInterface
{
    use TimestampableTrait;
    use ToggleableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string')]
    protected ?string $source = null;

    #[ORM\Column(type: 'string')]
    protected ?string $destination = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $permanent = true;

    #[ORM\Column(type: 'integer')]
    protected int $count = 0;

    #[ORM\Column(name: 'last_accessed', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $lastAccessed = null;

    #[ORM\Column(name: 'enabled', type: 'boolean')]
    protected bool $enabled = true;

    #[ORM\Column(name: 'only_404', type: 'boolean')]
    protected bool $only404 = true;

    #[ORM\Column(name: 'keep_query_string', type: 'boolean', options: ['default' => 0])]
    protected bool $keepQueryString = false;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected ?DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $updatedAt = null;

    /** @var Collection<array-key, ChannelInterface> */
    #[ORM\ManyToMany(targetEntity: ChannelInterface::class)]
    #[ORM\JoinTable(name: 'setono_sylius_redirect__redirect_channels')]
    #[ORM\JoinColumn(name: 'redirect_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'channel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Collection $channels;

    public function __construct()
    {
        $this->channels = new ArrayCollection();
        $this->createdAt = new DateTime();
    }

    // ... all existing methods unchanged ...
}
```

Note: The `createdAt` and `updatedAt` fields were previously handled by Gedmo timestampable. Since Sylius 2.x doesn't ship Gedmo by default, we initialise `createdAt` in the constructor and handle `updatedAt` via the existing `TimestampableTrait`. If the `TimestampableTrait` from Sylius handles these fields, we can rely on it. Otherwise, we set `createdAt` in the constructor.

The `enabled` field needs to be explicitly mapped since `ToggleableTrait` provides the property but not the mapping (which was in the XML before).

**Step 2: Delete the XML mapping file**

```bash
rm config/doctrine/model/Redirect.orm.xml
rmdir config/doctrine/model config/doctrine 2>/dev/null || true
```

**Step 3: Commit**

```bash
git add -A
git commit -m "Convert Doctrine mapping from XML to PHP attributes"
```

---

### Task 5: Update Configuration.php — remove driver config

**Files:**
- Modify: `src/DependencyInjection/Configuration.php`

**Step 1: Simplify Configuration**

In Sylius 2.x, the resource bundle config format has changed. The `driver` node is still supported but should reference the correct constant. The main thing is ensuring `TreeBuilder` uses `getRootNode()` correctly (which it already does).

Keep the existing `Configuration.php` as-is — it already uses the modern `TreeBuilder` pattern. The resource configuration with `classes` nodes is still valid in Sylius 2.x ResourceBundle.

Actually, no changes needed here. Skip this task.

---

### Task 5 (revised): Update RemoveRedirectsCommand to use PHP 8 attribute

**Files:**
- Modify: `src/Command/RemoveRedirectsCommand.php`

**Step 1: Replace static properties with #[AsCommand] attribute**

```php
<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Command;

use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'setono:sylius-redirect:remove',
    description: 'This command will remove redirects that have not been accessed later than x days ago where x is the `setono_sylius_redirect.remove_after` parameter',
)]
class RemoveRedirectsCommand extends Command
{
    private RedirectRepositoryInterface $redirectRepository;

    private int $removeAfter;

    public function __construct(RedirectRepositoryInterface $redirectRepository, int $removeAfter)
    {
        parent::__construct();

        $this->redirectRepository = $redirectRepository;
        $this->removeAfter = $removeAfter;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->redirectRepository->removeNotAccessed($this->removeAfter);

        return Command::SUCCESS;
    }
}
```

**Step 2: Commit**

```bash
git add src/Command/RemoveRedirectsCommand.php
git commit -m "Use #[AsCommand] attribute for console command"
```

---

### Task 6: Convert admin template from SemanticUI to Bootstrap

**Files:**
- Modify: `templates/Admin/Redirect/_form.html.twig`

**Step 1: Convert SemanticUI markup to Bootstrap 5**

```twig
<div class="row">
    <div class="col-md-4">
        {{ form_errors(form) }}
        <div class="card mb-3">
            <div class="card-body">
                {{ form_row(form.source) }}
                {{ form_row(form.destination) }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body">
                {{ form_row(form.enabled) }}
                {{ form_row(form.permanent) }}
                <div class="alert alert-info">
                    {{ 'setono_sylius_redirect.form.redirect.permanent_help'|trans }}
                </div>
                {{ form_row(form.only404) }}
                <div class="alert alert-info">
                    {{ 'setono_sylius_redirect.form.redirect.only_404_help'|trans }}
                </div>
                {{ form_row(form.keepQueryString) }}
                <div class="alert alert-info">
                    {{ 'setono_sylius_redirect.form.redirect.keep_query_string_help'|trans }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body">
                {{ form_row(form.channels) }}
                <div class="alert alert-info">
                    {{ 'setono_sylius_redirect.form.redirect.channels_help'|trans }}
                </div>
            </div>
        </div>
    </div>
</div>
```

**Step 2: Commit**

```bash
git add templates/Admin/Redirect/_form.html.twig
git commit -m "Convert admin form template from SemanticUI to Bootstrap"
```

---

### Task 7: Update block event listeners for Sylius 2.x

**Files:**
- Modify: `config/services/block_event_listener.xml`

**Step 1: Evaluate block event listeners**

Sylius 2.x replaces Sonata block events with Twig hooks (or Twig Component events). The current plugin uses `Sylius\Bundle\UiBundle\Block\BlockEventListener` to inject JavaScript for the slug update feature.

In Sylius 2.x, `BlockEventListener` and `sonata.block.event.*` events no longer exist. The JavaScript files (`updateSlug.js`, `updateProductSlug.js`, `updateTaxonSlug.js`) handle showing/hiding the "add automatic redirect" checkbox when a slug changes.

**Option:** Since Sylius 2.x uses Twig Live Components for product/taxon forms, the JS-based slug monitoring approach may not work the same way. The bitExpert PR noted this was "hackish".

For now, **remove the block event listeners entirely** and the associated JS files. The form extension (`AutomaticRedirectTypeExtension`) still adds the checkbox field — it just won't have the JS to auto-toggle it. Users can manually check it. This is a conscious simplification; the JS integration can be re-added later with Twig hooks if needed.

Replace `config/services/block_event_listener.xml`:

```xml
<?xml version="1.0" encoding="UTF-8" ?>

<container xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://symfony.com/schema/dic/services"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">
    <services>
        <!-- Block event listeners removed for Sylius 2.x compatibility.
             Sonata block events are no longer available. The automatic redirect
             checkbox is still added by the form extension but without JS auto-toggle. -->
    </services>
</container>
```

**Step 2: Remove JS files and template includes**

```bash
rm -rf public/
rm templates/Admin/_javascripts.html.twig
rm templates/Admin/Product/_javascripts.html.twig
rm templates/Admin/Taxon/_javascripts.html.twig
rmdir templates/Admin/Product templates/Admin/Taxon 2>/dev/null || true
```

**Step 3: Commit**

```bash
git add -A
git commit -m "Remove Sonata block event listeners and JS assets (incompatible with Sylius 2.x)"
```

---

### Task 8: Update grid configuration for Sylius 2.x

**Files:**
- Modify: `config/grids/setono_sylius_redirect_admin_redirect.yaml`

**Step 1: Check grid template references**

The grid references `@SyliusUi/Grid/Field/yesNo.html.twig` which may have moved in Sylius 2.x. In Sylius 2.x with Bootstrap, the grid field templates are in `@SyliusBootstrapAdminUi` or still available at `@SyliusUi`.

Check if the template exists — if Sylius 2.x ships it, no change needed. If it moved, update the path. Most likely it's still at `@SyliusUi/Grid/Field/yesNo.html.twig` or needs updating to `@SyliusBootstrapAdminUi/grid/field/yesNo.html.twig`.

For safety, update to use the Sylius 2.x grid field template paths if they changed. This should be verified during testing. Leave as-is for now and verify in Task 11.

No changes needed at this step — the grid YAML config format hasn't changed.

---

### Task 9: Update admin routing for Sylius 2.x

**Files:**
- Modify: `config/admin_routing.yaml`

**Step 1: Check routing format**

The Sylius 2.x resource routing format may require updating. The `type: sylius.resource` format is still supported. The `templates: "@SyliusAdmin\\Crud"` reference should still work as Sylius 2.x admin bundle provides these templates.

No changes needed to the routing file itself. The references will resolve correctly.

---

### Task 10: Remove old test application and add sylius/test-application config

**Files:**
- Delete: `tests/Application/` (entire directory)
- Create: `tests/TestApplication/.env`
- Create: `tests/TestApplication/config/bundles.php` (if needed)

**Step 1: Remove old test application**

```bash
rm -rf tests/Application
```

**Step 2: Create test application env file**

Create `tests/TestApplication/.env`:

```dotenv
DATABASE_URL=mysql://root@127.0.0.1/test_application_%kernel.environment%

SYLIUS_TEST_APP_CONFIGS_TO_IMPORT="@SetonoSyliusRedirectPlugin/config/app/config.yaml"
SYLIUS_TEST_APP_ROUTES_TO_IMPORT="@SetonoSyliusRedirectPlugin/config/admin_routing.yaml"
SYLIUS_TEST_APP_BUNDLES_TO_ENABLE="Setono\SyliusRedirectPlugin\SetonoSyliusRedirectPlugin"
```

**Step 3: Commit**

```bash
git add -A
git commit -m "Replace tests/Application with sylius/test-application configuration"
```

---

### Task 11: Clean up tooling config (ECS, Rector, Psalm)

**Files:**
- Modify: `ecs.php`
- Modify: `rector.php`
- Delete: `psalm.xml`, `psalm-baseline.xml`
- Delete: `composer-dependency-analyser.php` (if present)

**Step 1: Update ecs.php for new ECS API**

```php
<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withSkip(['tests/Application/**', 'tests/TestApplication/**'])
;
```

**Step 2: Update rector.php**

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withSkip([__DIR__ . '/tests/Application', __DIR__ . '/tests/TestApplication'])
    ->withPhpSets(php82: true)
;
```

**Step 3: Remove psalm files**

```bash
rm -f psalm.xml psalm-baseline.xml composer-dependency-analyser.php
```

**Step 4: Commit**

```bash
git add -A
git commit -m "Update tooling configuration for PHP 8.2+"
```

---

### Task 12: Update CI workflow

**Files:**
- Modify: `.github/workflows/build.yaml`

**Step 1: Update build matrix**

This is a larger file. Key changes:
- PHP versions: `8.2`, `8.3`, `8.4`
- Symfony versions: `6.4`, `7.1`
- Sylius version: `2.0`
- Node version: `20`
- Remove psalm step, update ECS invocation
- Update test-application setup commands

The exact content depends on the current workflow structure. Adapt the existing workflow file to match the new dependency versions. This can be fine-tuned after the core code changes are verified.

**Step 2: Commit**

```bash
git add .github/workflows/build.yaml
git commit -m "Update CI workflow for Sylius 2.x matrix"
```

---

### Task 13: Verify — install dependencies and run static checks

**Step 1: Install dependencies**

```bash
composer install --no-interaction
```

Expected: Should resolve all dependencies successfully.

**Step 2: Run phpspec (if applicable)**

```bash
vendor/bin/phpspec run
```

**Step 3: Fix any issues found**

Address any compatibility issues that surface during installation or static analysis. Common issues:
- Missing interfaces/classes due to Sylius 2.x namespace changes
- Deprecated method calls
- Type incompatibilities with updated dependencies

**Step 4: Commit fixes**

```bash
git add -A
git commit -m "Fix compatibility issues found during dependency resolution"
```

---

### Task 14: Final review and tag

**Step 1: Verify all files are in order**

```bash
git status
git log --oneline sylius-2.x --not v2.7.0
```

**Step 2: Tag (after approval)**

```bash
git tag v4.0.0
```

The version `4.0.0` avoids conflict with both upstream's `v2.7.0` and the SOAP fork's `3.0.0`.

---

## Risk Notes

- **Grid field templates**: The `@SyliusUi/Grid/Field/yesNo.html.twig` template path may need updating for Sylius 2.x. Verify during Task 13.
- **Slug auto-redirect JS**: Removed in Task 7. The checkbox still exists but won't auto-show. Can be re-implemented with Twig hooks later.
- **Gedmo timestampable**: The XML mapping used Gedmo for `createdAt`/`updatedAt`. The PHP attribute mapping initialises `createdAt` in constructor instead. Verify Sylius's `TimestampableTrait` behaviour in 2.x.
- **Form extensions**: `ProductTranslationTypeExtension` and `TaxonTranslationTypeExtension` extend Sylius form types. Verify these form type classes still exist at the same FQCN in Sylius 2.x.
- **`sylius.security.shop_regex` parameter**: Used by `SourceRegexValidator`. Verify this parameter still exists in Sylius 2.x.
