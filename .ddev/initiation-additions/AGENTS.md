# AGENTS.md

## Code Style & Naming Conventions
- **Variable & Property Naming:** Always use `camelCase` over `snake_case`.
  - *Exceptions:* Use `snake_case` when maintaining existing `snake_case` code within an existing file.

## PHP Guidelines
- **Conditional Checks:** Use `empty()` inside `if` statements where evaluating falsy/empty values is appropriate.

## Vue Guidelines
- **Single File Component Structure:** In `.vue` files, always place the `<template>` block at the top, preceding `<script>` and `<style>` blocks.

## Testing Strategy
- Run **only relevant or newly created tests** during iteration. Do NOT run full test suites on minor changes, and do NOT create or run temporary in-memory test scripts / test suites.
