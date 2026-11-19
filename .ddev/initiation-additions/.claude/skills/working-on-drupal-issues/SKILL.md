---
name: working-on-drupal-issues
description: Work on drupal.org (git.drupalcode.org) issues via the drupalcode MCP - check whether a problem is already reported or fixed upstream, create issues with the Drupal template, issue branches, MRs, review comments. Meant for general issues in Drupal modules. Use whenever told to create a Drupal issue.
---

# Working on Drupal.org issues (git.drupalcode.org)

Drupal.org issues are GitLab **work items** on git.drupalcode.org. Use the `mcp__drupalcode__*` tools; load them via ToolSearch first, e.g. `select:mcp__drupalcode__list_work_items,mcp__drupalcode__save_work_item,...`.

## Requirements

The drupalcode MCP server (`.mcp.json`) authenticates with `DRUPALCODE_TOKEN`, a fine-grained personal access token from git.drupalcode.org, set in `.ddev/.env.web`.

Check before doing anything else: if ToolSearch finds no `mcp__drupalcode__*` tools, or `get_user(me: true)` fails (401/403, `insufficient_scope`), stop. Tell the user to set `DRUPALCODE_TOKEN="glpat-..."` in `.ddev/.env.web`.

## Basics

- Project path: `project/<machine_name>` (e.g. `project/glossify`).
- Issue fork path: `issue/<machine_name>-<iid>` (e.g. `issue/glossify-3609476`). Each issue gets its own fork; all code changes go there (see the flow below).
- Current user: `get_user(me: true)` → your account is the one to assign: use its id for `assignee_ids`.
- Issue IDs (`iid`) are the work item IDs (e.g. `3609476`).
- Some projects are not migrated yet and still use the legacy issue queue on www.drupal.org, which the MCP can't see. If `list_work_items` returns nothing for an obviously active project, tell the user instead of creating an issue on git.drupalcode.org.
- Don't involve other people: no `@`-mentions, assignees or reviewers other than the current user, unless the user asks for it.
- Default/dev branch: `get_project` / `list_branches` (e.g. `3.x`, `4.x`, `8.x-1.x`, `2.1.x`). Target MRs at the active dev branch.
- Labels (scoped, per project, check with `search_labels`):
  - `category::bug|feature|task|support|plan`
  - `priority::critical|major|normal|minor`
  - `state::needsReview|needsWork|rtbc|fixed|postponed|...`

## Checking whether something is already reported / fixed upstream

Per module (≈10 tool calls max):

1. `list_work_items` with `search` keywords specific to the finding (function or class names, "XSS", "access", "cache", "exception" ...) and skim the ~30 most recently updated items (`sort: UPDATED_DESC`).
2. `list_merge_requests` (`state: all`) for related MRs.
3. Read the affected file at the dev branch HEAD with `get_repository_file` and check whether the problematic pattern is still there; `list_commits` for relevant recent fixes.
4. Note open MRs touching the same code (possible conflicts).

Issues are often reported publicly as *functional* bugs (crash, fatal error) without the security angle. Also check that "Fixed" issues actually landed in code (e.g. replicate_ui #2907005 was closed as fixed but never committed).

When checking more than 8 modules, fan out to parallel subagents (up to 8 modules each) with a read-only brief and a compact table as output.

## Follow-up questions in the final reply

Check the points below while working, but don't stop to ask about them. Stay in scope, finish the task, and then list what you found as questions at the end of your final reply, e.g. "I finished the fix and opened !12. I noticed that the module still supports Drupal 9, which is end-of-life. Should I drop support for it as well?" Only ask about points that apply; skip the rest.

- **Unrelated CI failures:** if linters (phpcs, ESLint, Stylelint, cspell, etc.) report problems in code you didn't touch, don't fix them. Ask "Should we address the unrelated CI failures as well?" and name the affected tools/files. PHPUnit is handled separately (see Tests).
- **Drupal core EOL versions:** check the Drupal core versions the module supports (`core_version_requirement` in the `.info.yml`). If it still includes an end-of-life core version, ask whether to drop support for it. If your fix needed a workaround only for that EOL version, say so, as dropping support would remove it.
- **Local checkout of the issue branch:** once the issue branch exists, ask "Should I fetch the issue branch and switch to it locally?" If yes:
  - Module already a git checkout in `modules/custom/<module>`: `git remote add <module>-<iid> https://git.drupalcode.org/issue/<module>-<iid>.git` (if missing), `git fetch <module>-<iid>`, then `git checkout -b <iid>-<slug> <module>-<iid>/<iid>-<slug>`.
  - Not in `modules/custom`: clone `https://git.drupalcode.org/project/<module>.git` into `modules/custom/<module>`, then add the fork remote and check out the issue branch as above.
  - Module also in `modules/contrib`: ask whether to replace the contrib module with the cloned repo on the issue branch. Never leave two copies of the module in place.

## Tests

- Always add a test that checks the originally reported issue, if not told otherwise.
- Make sure all of the module's tests pass, but run them only AFTER everything is implemented.
- Lint locally with the CI runners instead of waiting for the MR pipeline: `.ddev/drupal-ci/bin/<tool> <module path>` for `phpcs`, `phpstan`, `eslint`, `stylelint` and `cspell`. They use the same configs as the drupal.org CI. Findings in code you didn't touch fall under "Unrelated CI failures" above.

## Issue → branch → MR flow

Drupal does not use personal forks or branches in the project repo. Each issue gets a dedicated **issue fork** (`issue/<project>-<iid>`); the branch lives there and the MR goes from the fork to the project. Use the issue fork even if the user is a maintainer of the project, so others can collaborate on the branch. Commit to the project repo (`project/<project>`) only if the user explicitly asks for it.

1. **Create the issue** with `save_work_item` (`type_name: "Issue"`, `labels`, `assignee_ids`), description from the template below.
2. **Issue fork:** check whether it exists with `get_project("issue/<project>-<iid>")`. If not, create it by posting a comment containing only `/do:fork` on the issue (`save_note` with `work_item_iid`). Drupal.org provisions the fork asynchronously, so check `get_project` again up to 5 times, about 10 seconds apart.
   - The fork can NOT be created via `fork_repository` (`insufficient_granular_scope`) or by pushing. Don't try.
   - Creating the fork gives you push access. If someone else created it, you usually have none (commit fails with 403): post `/do:access` the same way, then retry.
   - If the fork still doesn't show up, give the user the "Manage forks" link from the bot comment on the issue (`https://new.drupal.org/drupalorg/issue-fork/management?source_link=https://git.drupalcode.org/project/<project>/-/work_items/<iid>`), ask them to click "Create issue fork", and wait.
3. **Issue branch** in the fork: `<iid>-<slug-of-title>`. If `list_branches` on the fork shows it, use it. Otherwise create it implicitly with `add_commit` (`project_id: "issue/<project>-<iid>"`, `start_project: "project/<project>"`, `start_branch: <dev branch>`), so it starts from the current upstream HEAD. Edit files via `old_str`/`new_str` actions (read the file at HEAD first). Never commit to the dev branch.
4. **Commit message / MR title** (conventional commits): `<type>: #<iid> <Issue title>`, e.g. `task: #3609476 Harden file relocation handling`. Types: feat, fix, docs, style, refactor, perf, test, build, ci, chore, revert, task.
5. **Security check** before opening the MR (see below).
6. **MR**: `save_merge_request` with `project_id: "issue/<project>-<iid>"` (source), `target_project_id: <numeric id of project/<project>>` (from `get_project`), `target_branch: <dev branch>`, description `Closes #<iid>` (this links the MR to the issue's Development widget), `assignee_ids`, `remove_source_branch: true`.
7. **Comment** on the issue (`save_note` with `work_item_iid`) summarizing the changes and how to test them manually (see below).
8. Set `state::needsReview` and assign the current user if possible. Otherwise don't assign anyone.

## Security check

When you think you are finished (after tests pass, before opening the MR), review the complete diff of the issue branch against the dev branch for security problems you might have introduced or missed:

- Access bypass: missing/incorrect permission or entity access checks, routes without `_permission`/`_access`, unchecked `accessCheck()` on entity queries.
- XSS: unescaped output, `#markup` with user input, `|raw` in Twig, `Markup::create()` / `FormattableMarkup` with untrusted data, JS `innerHTML`.
- SQL injection: concatenated queries instead of placeholders / the query builder.
- CSRF: state-changing GET routes without `_csrf_token`, forms bypassing Form API.
- Unsafe file handling, `unserialize()` on untrusted input, open redirects, information disclosure (stack traces, secrets).

Fix what you find before continuing.

Note: `save_note` cannot edit or delete existing comments. Get comment wording right before posting. Issue summaries and MR titles can be updated later.

## Issue summary template (official Drupal work item template)

Start with the AI disclosure, then fill the relevant sections:

```markdown
> **AI disclosure:** This issue summary and the proposed changes were drafted with the help of an AI assistant (Claude Code) and still need a human review.

## Problem/motivation

<!-- The bug, gap, who it affects, and links to related issues. Not the fix. -->

### Steps to reproduce

<!-- Version, exact steps, expected vs actual. Include logs in code blocks. -->

## Proposed resolution

<!-- The approach and why, including options rejected. Keep it current. -->

## Remaining tasks

- [ ] ...

## User interface changes

<!-- "None" if no UI change. -->

## API changes

<!-- Services, hooks, events, plugins, signatures, config schema, BC breaks + upgrade path. -->

## Data model changes

<!-- Schema, entities, fields, config; update path needed? "None" if none. -->
```

## Changes comment

```markdown
> **AI disclosure:** These changes and this comment were drafted with the help of an AI assistant (Claude Code) and still need a human review.

Created !<mr> (ready for review).

**Changes**
- `path/to/File.php`, `method()`: what changed and why (short).

**Manual testing**
Setup: ...
Tests: numbered, each with action + expected result, incl. a regression check.
```

Comprehensive, but no clutter.
