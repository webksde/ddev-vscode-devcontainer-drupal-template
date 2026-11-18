# AGENTS.md

## Environment

- The agent runs **inside** the ddev web container, in `/var/www/html`. Call tools directly
  (`vendor/bin/...`, `node_modules/.bin/...`, `drush`); do **not** prefix them with `ddev`.

## Code Style & Naming Conventions
- Drupal coding standards: 2-space indentation, 80-column comments/docs, US English
  (`color`, not `colour`; never `grey`).
- **Variable & Property Naming:** Always use `camelCase` over `snake_case`.
  - *Exceptions:* Use `snake_case` when maintaining existing `snake_case` code within an existing file.

## PHP Guidelines
- **Conditional Checks:** Use `empty()` inside `if` statements where evaluating falsy/empty values is appropriate.

### PHP coding standards (phpcs / phpcbf)

```bash
vendor/bin/phpcs -q --report=emacs \
  --standard=Drupal,DrupalPractice \
  --extensions=php,module,inc,install,test,profile,theme,info,txt,md,yml \
  <files-or-dir>
```

- Autofix: `vendor/bin/phpcbf` with the same arguments. Only run it on files you
  changed, and check the diff afterwards.
- Remember the PHP extensions Drupal uses: `.module`, `.install`, `.theme`, `.inc`,
  `.profile`.

### PHP static analysis (phpstan)

```bash
vendor/bin/phpstan analyse --no-progress --error-format=raw <files-or-dir>
```

- Errors about classes that don't exist (`class.notFound`) because a module's
  composer dependency isn't installed here are environment noise. Don't "fix" them.

## JavaScript Guidelines (ESLint + Prettier)

```bash
node_modules/.bin/eslint -c web/.eslintrc.json <files>
```

- Autofix: add `--fix`.

## Vue Guidelines
- **Single File Component Structure:** In `.vue` files, always place the `<template>` block at the top, preceding `<script>` and `<style>` blocks.

## Spelling (cspell)

```bash
node_modules/.bin/cspell --no-progress --no-summary <files>
```

- Uses the root `cspell.json` (Drupal core dictionary plus php/html/software terms;
  flags `grey` and `hte`). Files must be inside `/var/www/html`, because cspell
  silently fails on files outside `globRoot`.
- If the module has a `.cspell-project-words.txt`, load it as an extra dictionary,
  as drupal.org CI does:

  ```bash
  M=web/modules/<...>/<module>
  cfg=$(mktemp --suffix=.json)
  printf '{"version":"0.2","import":["%s/web/core/.cspell.json"],"dictionaryDefinitions":[{"name":"project-words","path":"%s/.cspell-project-words.txt"}],"dictionaries":["project-words"]}' \
    "$PWD" "$PWD/$M" > "$cfg"
  node_modules/.bin/cspell --no-progress --no-summary -c "$cfg" <files>
  rm -f "$cfg"
  ```

- Fix real misspellings. Only add a word to `.cspell-project-words.txt` when it is
  intentional (a name, API term or identifier). Keep that file sorted.

## Testing Strategy
- Tests: `vendor/bin/phpunit -c phpunit.xml <path>`.
- Run **only relevant or newly created tests** during iteration. Do NOT run full test suites on minor changes, and do NOT create or run temporary in-memory test scripts / test suites.
