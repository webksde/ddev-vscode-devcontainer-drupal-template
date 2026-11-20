# AGENTS.md

## Environment

- The agent runs **inside** the ddev web container, in `/var/www/html`. Call tools directly
  (`vendor/bin/...`, `.ddev/drupal-ci/bin/...`, `drush`); do **not** prefix them with `ddev`.

## Code Style & Naming Conventions
- **Variable & Property Naming:** Always use `camelCase` over `snake_case`.
  - *Exceptions:* Use `snake_case` when maintaining existing `snake_case` code within an existing file.

## PHP Guidelines
- **Conditional Checks:** Use `empty()` inside `if` statements where evaluating falsy/empty values is appropriate.

## Linting & static analysis (like drupal.org GitLab CI)

Always lint with the runners in `.ddev/drupal-ci/bin/`. They behave like the validate jobs of the drupal.org GitLab CI templates: each path is checked as
part of its module / theme.
Do **not** call `vendor/bin/phpcs`, `vendor/bin/phpstan`, `eslint`, `stylelint` or `cspell` directly.

Run all checks at once with `drupal-ci` (phpcs, phpstan, eslint, stylelint, cspell and phpunit, like the MR
pipeline). It continues after failures and prints a summary at the end:

```bash
.ddev/drupal-ci/bin/drupal-ci <paths>                  # everything, incl. the module's tests
.ddev/drupal-ci/bin/drupal-ci --skip=phpunit <paths>   # lint only (while iterating)
.ddev/drupal-ci/bin/drupal-ci --fix <paths>            # autofix first (phpcbf, eslint/stylelint --fix)
.ddev/drupal-ci/bin/drupal-ci --only=phpcs,cspell <paths>
```

You can also run the single runners via:

```bash
.ddev/drupal-ci/bin/phpcs     <paths>   # PHP_CodeSniffer
.ddev/drupal-ci/bin/phpcbf    <paths>   # PHP_CodeSniffer autofix
.ddev/drupal-ci/bin/phpstan   <paths>   # PHPStan
.ddev/drupal-ci/bin/eslint    <paths>   # JavaScript + YAML; add --fix to autofix
.ddev/drupal-ci/bin/stylelint <paths>   # CSS; add --fix to autofix
.ddev/drupal-ci/bin/cspell    <paths>   # Spelling
.ddev/drupal-ci/bin/phpunit   <paths>   # PHPUnit: a module's tests/ folder, or single test files
```

- `<paths>` are files or folders, e.g. `web/modules/custom/my_module` or the
  files you changed. A folder with several modules (e.g. `web/modules/custom`)
  checks each of them. Only lint the code you work on, not the whole site.
- Extra tool options must use the `--name=value` form (e.g.
  `--report=emacs` for phpcs, `--error-format=raw` for phpstan), or go after
  `--`. The exit code is non-zero when there are findings.
- `cspell --words-only --unique <paths>` lists only the unknown words.
- PHPStan errors about classes that don't exist (`class.notFound`) because a
  module's composer dependency isn't installed here are environment noise.
  Don't "fix" them.
- Spelling: fix real misspellings. Only add a word to the module's
  `.cspell-project-words.txt` when it is intentional (e.g. a name, API term or
  identifier). Keep that file sorted.

## Vue Guidelines
- **Single File Component Structure:** In `.vue` files, always place the `<template>` block at the top, preceding `<script>` and `<style>` blocks.

## Testing Strategy
- Tests: `.ddev/drupal-ci/bin/phpunit <path>` (a module path runs its `tests/` folder, like CI; also accepts
  test files and `--filter=<name>`).
- Run **only relevant or newly created tests** during iteration. Do NOT run full test suites on minor changes.
