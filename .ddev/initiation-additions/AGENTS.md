# AGENTS.md

## Environment

- The agent runs **inside** the ddev web container, in `/var/www/html`. Call tools directly
  (`vendor/bin/...`, `.ddev/drupal-ci/bin/...`, `drush`); do **not** prefix them with `ddev`.

## Code Style & Naming Conventions
- Drupal coding standards: 2-space indentation, 80-column comments/docs, US English
  (`color`, not `colour`; never `grey`).
- **Variable & Property Naming:** Always use `camelCase` over `snake_case`.
  - *Exceptions:* Use `snake_case` when maintaining existing `snake_case` code within an existing file.

## PHP Guidelines
- **Conditional Checks:** Use `empty()` inside `if` statements where evaluating falsy/empty values is appropriate.

## Linting & static analysis (like drupal.org GitLab CI)

Always lint with the runners in `.ddev/drupal-ci/bin/`. They behave like the
validate jobs of the drupal.org GitLab CI templates: each path is checked as
part of its project (the module, theme, profile or recipe it belongs to), from
the project's root, with the project's own config files (`phpcs.xml.dist`,
`phpstan.neon`, `.eslintrc.json`, `.stylelintrc.json`, `.cspell.json`,
`.cspell-project-words.txt`, ...) and the CI defaults where it has none. Do
**not** call `vendor/bin/phpcs`, `vendor/bin/phpstan`, `eslint`, `stylelint` or
`cspell` directly, and do not pass your own `--standard`, `--config` or level:
the results would differ from CI.

```bash
.ddev/drupal-ci/bin/phpcs     <paths>   # PHP_CodeSniffer
.ddev/drupal-ci/bin/phpcbf    <paths>   # PHP_CodeSniffer autofix
.ddev/drupal-ci/bin/phpstan   <paths>   # PHPStan
.ddev/drupal-ci/bin/eslint    <paths>   # JavaScript + YAML; add --fix to autofix
.ddev/drupal-ci/bin/stylelint <paths>   # CSS; add --fix to autofix
.ddev/drupal-ci/bin/cspell    <paths>   # Spelling
```

- `<paths>` are files or folders, e.g. `web/modules/custom/my_module` or the
  files you changed. A folder with several projects (e.g. `web/modules/custom`)
  checks each of them. Only lint the code you work on, not the whole site.
- Extra tool options must use the `--name=value` form (e.g.
  `--report=emacs` for phpcs, `--error-format=raw` for phpstan), or go after
  `--`. The exit code is non-zero when there are findings.
- `cspell --words-only --unique <paths>` lists only the unknown words.
- Only run `phpcbf` / `--fix` on files you changed, and check the diff afterwards.
- Remember the PHP extensions Drupal uses: `.module`, `.install`, `.theme`,
  `.inc`, `.profile`.
- PHPStan errors about classes that don't exist (`class.notFound`) because a
  module's composer dependency isn't installed here are environment noise.
  Don't "fix" them.
- Spelling: fix real misspellings. Only add a word to the project's
  `.cspell-project-words.txt` when it is intentional (a name, API term or
  identifier). Keep that file sorted.
- If core's node modules are missing (e.g. after a core update), the JS runners
  install them on first use; this takes a minute.

## Vue Guidelines
- **Single File Component Structure:** In `.vue` files, always place the `<template>` block at the top, preceding `<script>` and `<style>` blocks.

## Testing Strategy
- Tests: `vendor/bin/phpunit -c phpunit.xml <path>`.
- Run **only relevant or newly created tests** during iteration. Do NOT run full test suites on minor changes, and do NOT create or run temporary in-memory test scripts / test suites.
