---
name: working-on-drupal-issues
description: Work on drupal.org (git.drupalcode.org) issues via the drupalcode MCP - create issues with the Drupal template, work on new or existing issues, issue forks, MRs, review comments. Meant for general issues in Drupal modules. Use whenever told to create or work on a Drupal issue.
---

# Working on Drupal.org issues (git.drupalcode.org)

Drupal.org issues are GitLab **work items** on git.drupalcode.org. Use the `mcp__drupalcode__*` tools; load them via ToolSearch first, e.g. `select:mcp__drupalcode__list_work_items,mcp__drupalcode__save_work_item,...`.

## Requirements

The drupalcode MCP server (`.mcp.json`) authenticates with `DRUPALCODE_TOKEN`, a fine-grained personal access token from git.drupalcode.org, set in `.ddev/.env.web`.

Check before doing anything else: if ToolSearch finds no `mcp__drupalcode__*` tools, or `get_user(me: true)` fails (401/403, `insufficient_scope`), stop. Tell the user to set `DRUPALCODE_TOKEN="your-fine-grained-token"` in `.ddev/.env.web`.

## Basics

- Project path: `project/<machine_name>` (e.g. `project/glossify`).
- Issue fork path: `issue/<machine_name>-<iid>` (e.g. `issue/glossify-3609476`). Each issue gets its own fork; all code changes go there (see the flow below).
- Current user: `get_user(me: true)` → your account is the one to assign: use its id for `assignee_ids`.
- Issue IDs (`iid`) are the work item IDs (e.g. `3609476`).
- Some projects are not migrated yet and still use the legacy issue queue on www.drupal.org, which the MCP can't see. If `list_work_items` returns nothing for an obviously active project, tell the user instead of creating an issue on git.drupalcode.org.
- Don't involve other people: no `@`-mentions, assignees or reviewers other than the current user, unless the user asks for it.
- `save_note` cannot edit or delete existing comments. Get comment wording right before posting. Issue summaries and MR titles can be updated later.
- Default/dev branch: `get_project` / `list_branches` (e.g. `3.x`, `4.x`, `8.x-1.x`, `2.1.x`). Target MRs at the active dev branch.
- Labels (scoped, per project, check with `search_labels`):
  - `category::bug|feature|task|support|plan`
  - `priority::critical|major|normal|minor`
  - `state::needsReview|needsWork|rtbc|fixed|postponed|...`

## Follow-up questions in the final reply

Check the points below at the end, once the task is finished. Stay in scope, then list what you found as questions at the end of your final reply. Only ask about points that apply; skip the rest.

- **Unrelated CI failures:** run all CI checks on the whole module with `.ddev/drupal-ci/bin/drupal-ci <module path>`. If it reports problems in code you didn't touch, including failing PHPUnit tests unrelated to your change, don't fix them. Ask "Should we address the unrelated CI failures as well?" and name the affected tools, files and tests.
- **Drupal core EOL versions:** check the Drupal core versions the module supports (`core_version_requirement` in the `.info.yml`) against the official release schedule (https://www.drupal.org/about/core/policies/core-release-cycles/schedule); don't rely on what you remember. Only if a supported version is already end-of-life, ask whether to drop support for it. If your fix needed a workaround only for that EOL version, say so, as dropping support would remove it. If no supported version is EOL, don't mention it.

## Tests

- Always add a test that checks the originally reported issue, if not told otherwise.
- Make sure the module's tests related to the fixed issue (new and existing ones covering the changed code) pass.

## Issue → branch → MR flow

Drupal does not use personal forks or branches in the project repo. Each issue gets a dedicated **issue fork** (`issue/<project>-<iid>`); the branch lives there and the MR goes from the fork to the project. Use the issue fork even if the user is a maintainer of the project, so others can collaborate on the branch. Commit to the project repo (`project/<project>`) only if the user explicitly asks for it.

1. **Issue:**
   - New issue: create it with `save_work_item` (`type_name: "Issue"`, `labels`, `assignee_ids`), description from the template below.
   - Existing issue: skip creating it. Read its description and all comments first (`get_work_item` with its notes and related merge requests), and check for an existing issue fork. If a maintainer questioned or rejected the approach, you may still implement it locally, but ask the user before pushing or opening an MR.
2. **Local checkout:** work in a local git checkout of the project, so you can run tests and linters.
   - Module already a git checkout of `project/<module>` (anywhere under `web/modules/`): use it.
   - Otherwise: clone `https://git.drupalcode.org/project/<module>.git` into `web/modules/custom/<module>`. If the module is also in `modules/contrib`, ask whether to replace the contrib copy before continuing. Never leave two copies of the module in place.
   - If the issue fork already exists (step 1), add it as a remote and fetch it:
     ```bash
     git remote add <project>-<iid> git@git.drupalcode.org:issue/<project>-<iid>.git
     git fetch <project>-<iid>
     ```
     If it has a branch for this issue (usually the one of an open MR), continue on it: `git checkout -b <branch> <project>-<iid>/<branch>`.
   - Otherwise create the issue branch `<iid>-<slug-of-title>` from the current upstream dev branch (`git fetch origin`, `git checkout -b <branch> origin/<dev branch>`). Never commit to the dev branch.
3. **Implement**, add tests and lint (see Tests). **Commit message / MR title** (conventional commits): `<type>: #<iid> <Issue title>`, e.g. `task: #3609476 Harden file relocation handling`. Types: feat, fix, docs, style, refactor, perf, test, build, ci, chore, revert, task.
4. **Security check** before pushing (see below). Pushing publishes the code.
5. **Issue fork:** check whether it exists with `get_project("issue/<project>-<iid>")`. If not, create it by posting a comment containing only `/do:fork` on the issue. The MCP (`save_note`) rejects comments starting with `/`, so post it via the REST API (`$DRUPALCODE_TOKEN` is set in the container; URL-encode the `/` in the project path as `%2F`):
   ```bash
   curl -sS --fail-with-body -w '\nHTTP %{http_code}\n' \
     -H "PRIVATE-TOKEN: $DRUPALCODE_TOKEN" --data-urlencode "body=/do:fork" \
     "https://git.drupalcode.org/api/v4/projects/project%2F<project>/issues/<iid>/notes"
   ```
   Expect `HTTP 201`. Drupal.org provisions the fork asynchronously, so check `get_project` again up to 5 times, about 10 seconds apart.
   - The fork can NOT be created via `fork_repository` (`insufficient_granular_scope`) or by pushing. Don't try.
   - Creating the fork gives you push access. If someone else created it, you usually have none (push is denied): post `/do:access` the same way (`body=/do:access`), then retry.
   - If the fork still doesn't show up, give the user the "Manage forks" link from the bot comment on the issue (`https://new.drupal.org/drupalorg/issue-fork/management?source_link=https://git.drupalcode.org/project/<project>/-/work_items/<iid>`), ask them to click "Create issue fork", and wait.
6. **Push** the branch to the issue fork with git over SSH (the user's SSH agent is available in the container). Add the remote first if step 2 didn't:
   ```bash
   git remote add <project>-<iid> git@git.drupalcode.org:issue/<project>-<iid>.git
   git push <project>-<iid> <branch>
   ```
   - Rejected because others pushed in the meantime: `git pull --rebase <project>-<iid> <branch>`, then push again. Never force-push over other people's commits.
   - `Permission denied (publickey)`: the user's SSH key isn't loaded or registered on git.drupalcode.org. Tell the user and wait. Access denied to the fork: post `/do:access` (step 5) and retry.
7. **MR**: `save_merge_request` with `project_id: "issue/<project>-<iid>"` (source), `target_project_id: <numeric id of project/<project>>` (from `get_project`), `target_branch: <dev branch>`, description `Closes #<iid>` (this links the MR to the issue's Development widget), `assignee_ids`, `remove_source_branch: true`.
8. **Comment** on the issue (`save_note` with `work_item_iid`) summarizing the changes and how to test them manually (see below).
9. Set `state::needsReview`.

## Security check

When you think you are finished (after tests pass, before pushing), review the complete diff of the issue branch against the dev branch for security problems you might have introduced or missed:

- Access bypass: missing/incorrect permission or entity access checks, routes without `_permission`/`_access`, unchecked `accessCheck()` on entity queries.
- XSS: unescaped output, `#markup` with user input, `|raw` in Twig, `Markup::create()` / `FormattableMarkup` with untrusted data, JS `innerHTML`.
- SQL injection: concatenated queries instead of placeholders / the query builder.
- CSRF: state-changing GET routes without `_csrf_token`, forms bypassing Form API.
- Unsafe file handling, `unserialize()` on untrusted input, open redirects, information disclosure (stack traces, secrets).

Fix what you find before continuing.

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
