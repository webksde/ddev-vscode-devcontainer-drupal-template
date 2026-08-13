# AGENTS.md

## General & Output Rules
- **No Unsolicited Documentation/Summaries:** Do not create or update `README` files, and do not provide a summary of changes unless explicitly instructed.
- **No Unsolicited Tests:** Do not generate test files or write test cases unless explicitly requested.
- **No Test Execution:** Do not run any automated tests or test suites.
- **No Git Commands:** Do not execute any Git commands (e.g., `git status`, `git commit`, `git checkout`).
- **No Linting Commands:** Do not run linter tools or automated fixers (e.g., `phpcs`, `phpcbf`, `eslint`).
- **Do Not Fix Linter Errors:** Ignore any existing linter errors; leave them as-is.

## Code Style & Naming Conventions
- **Variable & Property Naming:** Always use `camelCase` over `snake_case`.
  - *Exceptions:* Use `snake_case` for `$form_state` or when maintaining existing `snake_case` code within an existing file.

## PHP Guidelines
- **Class Resolution:** Always use fully resolved class names.
  - For un-namespaced global classes, prefix them with a leading backslash (e.g., `\DateTime`).
- **Docblocks & Comments:** Use fully qualified class names (FQCN) inside PHPDoc annotations and inline comments.
- **Traits Exception:** The FQCN and namespace prefixing rules do not apply to PHP Traits.
- **Conditional Checks:** Use `empty()` inside `if` statements where evaluating falsy/empty values is appropriate.

## Vue Guidelines
- **Single File Component Structure:** In `.vue` files, always place the `<template>` block at the top, preceding `<script>` and `<style>` blocks.