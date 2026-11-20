# DDEV Drupal Template With Attached VSCode
The tools inside this repository will create a modified Drupal environment, focused on development and debugging.

**DO NOT USE IN PRODUCTION - This is a local development tool!**

## Quick-Start
**Spin up a ready-to-code Drupal 10/11 CMS DDEV based development container with preconfigured Drupal Best-Practice Dev-Modules and Visual Studio Code Settings/Extensions in three commands!** 🚀

Initialization takes about 5 minutes and is only needed once. Make sure you meet the [prerequisites](#prerequisites) first.

Start a **standard** dev environment using Drupal 11, meant for testing and working on contrib modules / issues:
```
git clone https://github.com/webksde/ddev-vscode-devcontainer-drupal-template.git standard-vscode-drupal && cd standard-vscode-drupal && ddev drowl-init
```
Use `ddev drowl-init -v 10` to install Drupal 10 instead.

Or start a **core** dev environment using gitified Drupal 12.x-dev, meant for working on core issues:
```
git clone https://github.com/webksde/ddev-vscode-devcontainer-drupal-template.git core-vscode-drupal && cd core-vscode-drupal && ddev drowl-init-dev
```

Afterwards:
- Use `ddev describe` to check the status & URLs of your project.
- Log in with `admin` / `admin` (an additional user `max` / `max` is created as well).
- Use `ddev code` to open your prepared VSCode IDE attached to the web container. Inside VSCode, go to "Extensions" and search for `@recommended` to reveal and install the recommended extensions.

**Project names:** "standard-vscode-drupal" / "core-vscode-drupal" can be replaced with any name you like. The DDEV containers are named after it (e.g. "ddev-standard-vscode-drupal-web"). Names must be valid: "no-spaces-but-hyphens", "UpperAndLower" and "should.work.with.dots" work, **underscores are not allowed**.

### Typical Use-Cases
- Local Drupal development / testing / evaluation instance with a ready-to-go IDE
- Module / Theme development or evaluation
- Contrib module issue forks / merge requests / patch creation (Git clone / commit / push / ...)
- Drupal core development
- Simple & quick Drupal sandbox for offline / local use

## Prerequisites
1. Up to date versions of [DDEV](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/), Docker and Chrome/Firefox
2. VSCode installed on your machine locally
3. The [Remote Development Extension Pack for VSCode](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.vscode-remote-extensionpack) (`ms-vscode-remote.vscode-remote-extensionpack`)
4. (Optional) To debug inside the attached VSCode instance, run `ddev config global --xdebug-ide-location=container`
   - This option is only available globally, so you need to adjust it again when working with other DDEV projects where VSCode is not attached to the web container (e.g. when running the IDE in WSL, use `ddev config global --xdebug-ide-location=wsl2`).

---

## Features

Provides a plug and play 🔌 DDEV (Docker) based development environment with attached VSCode.

### VSCode Extensions (recommended)
- [Intelephense](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client)
- [PHP Debug (Using XDebug)](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug)
- [PHP DocBlocker](https://marketplace.visualstudio.com/items?itemName=neilbrayfield.php-docblocker)
- [PHP Sniffer & Beautifier](https://marketplace.visualstudio.com/items?itemName=ValeryanM.vscode-phpsab)
- [ESLint](https://marketplace.visualstudio.com/items?itemName=dbaeumer.vscode-eslint)
- [Prettier](https://marketplace.visualstudio.com/items?itemName=esbenp.prettier-vscode)
- [Stylelint](https://marketplace.visualstudio.com/items?itemName=stylelint.vscode-stylelint)
- [CSpell](https://marketplace.visualstudio.com/items?itemName=streetsidesoftware.code-spell-checker)
- [Twig Language 2](https://marketplace.visualstudio.com/items?itemName=mblode.twig-language-2)
- [Twig Code Snippets](https://marketplace.visualstudio.com/items?itemName=nadim-vscode.twig-code-snippets)
- [GitLens](https://marketplace.visualstudio.com/items?itemName=eamodio.gitlens)
- [TODO Highlight](https://marketplace.visualstudio.com/items?itemName=jgclark.vscode-todo-highlight)
- [Apache Conf](https://marketplace.visualstudio.com/items?itemName=mrmlnc.vscode-apache)
- [Composer](https://marketplace.visualstudio.com/items?itemName=DEVSENSE.composer-php-vscode)
- [Empty Indent](https://marketplace.visualstudio.com/items?itemName=dmitrydorofeev.empty-indent)
- [ShellCheck](https://marketplace.visualstudio.com/items?itemName=timonwong.shellcheck)
- [Claude Code](https://marketplace.visualstudio.com/items?itemName=Anthropic.claude-code)

A launch configuration for "Listen for XDebug" is included.

### CLI Tooling
- [Drush](https://www.drush.org/)
- [PHPUnit](https://phpunit.de/) (incl. [code coverage](https://docs.phpunit.de/en/main/code-coverage.html))
- [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer) (PHPCS / PHPCBF) with [Drupal Coder](https://www.drupal.org/project/coder) standards
- [PHPStan](https://phpstan.org/) with deprecation rules
- [ESLint](https://eslint.org/), [Stylelint](https://stylelint.io/) and [CSpell](https://cspell.org/)
- All linters behave like the drupal.org GitLab CI jobs (see [below](#linting-like-the-drupalorg-gitlab-ci))
- [XDebug](https://xdebug.org/) and [Kint](https://kint-php.github.io/kint/)
- [Claude Code CLI](https://docs.anthropic.com/en/docs/claude-code) (see [below](#claude-code-in-the-web-container))
- [DDEV Tooling](https://ddev.readthedocs.io/en/stable/users/usage/cli/)

### Drupal Development Modules
Installed and enabled by `ddev drowl-init`:
- [Devel](https://www.drupal.org/project/devel) (incl. Devel Generate, Kint as default dumper)
- [Devel PHP](https://www.drupal.org/project/devel_php)
- [Coffee](https://www.drupal.org/project/coffee)
- [Backup Migrate](https://www.drupal.org/project/backup_migrate)
- [Stage File Proxy](https://www.drupal.org/project/stage_file_proxy)
- [Config Inspector](https://www.drupal.org/project/config_inspector)
- [Examples](https://www.drupal.org/project/examples)

`ddev drowl-init-dev` only enables Config Inspector, as most of the other modules are not yet Drupal 12 compatible.

Both commands also disable CSS / JS aggregation and enable verbose error logging.

Beautifully packaged for easy project and environment switching.

*Feel free to fork for other Frameworks or improve for lovely Drupal!* ❤️

---

## Documentation

### Tooling
- `ddev code`: attach VSCode to your running container.
- `ddev phpunit path/to/tests`: run tests using PHPUnit.
- `ddev phpunit-coverage path/to/cover`: create a test coverage report of the given directory.
- `ddev xdebug on`: turn on XDebug, then in VSCode go to "Run and Debug", start "Listen for XDebug" and open your project in the browser.

### Linting like the drupal.org GitLab CI
The linters behave like the validate jobs of the [drupal.org GitLab CI templates](https://git.drupalcode.org/project/gitlab_templates): every path is checked as part of its project (module, theme, profile or recipe), from the project's root, with the project's own config files (`phpcs.xml.dist`, `phpstan.neon`, `.eslintrc.json`, `.stylelintrc.json`, `.cspell.json`, `.cspell-project-words.txt`, ...), and the CI defaults where it has none. The JS tools come from core's `node_modules`, installed with yarn like in CI.
- `ddev phpcs web/modules/custom/my_module`: check PHP code against the Drupal coding standards (`ddev phpcbf ...` to autofix).
- `ddev phpstan web/modules/custom/my_module`: static analysis with PHPStan.
- `ddev eslint web/modules/custom/my_module (--fix)`: lint / autofix JavaScript and YAML.
- `ddev stylelint web/modules/custom/my_module (--fix)`: lint / autofix CSS.
- `ddev cspell web/modules/custom/my_module`: check spelling (`--words-only --unique` lists only the unknown words).
- `ddev drupal-ci-setup`: install core's node modules and link core's configs into `web/*/custom` (fallback for projects without their own config, also used by the VSCode extensions), and generate the `cspell.json` for the VSCode CSpell extension. Run it again after adding a custom module or theme, or after updating core.
- `ddev drupal-ci-update`: download the CI default configs (`phpcs.xml.dist`, `phpstan.neon`, `.cspell.json`) and helper scripts from the latest gitlab_templates 1.x release (`1.x-latest`, like contrib projects use) into `.ddev/drupal-ci/`. `drowl-init` runs it once; run it again to update them, then run `ddev drupal-ci-setup`.

Paths can also be single files, or folders containing several projects (e.g. `web/modules/custom`). Without a path, the current directory is checked. Inside the container (VSCode terminal, Claude Code), the same runners are available as `.ddev/drupal-ci/bin/<tool>`; the VSCode extensions use the same configs.

### Database
- `ddev import-db --database=db --file=db.sql.gz`: import a database file.
- `ddev drowl-reset-db`: reset the database to its state after initialization (`--before-contrib` resets it to the state before the contrib modules were enabled). The dumps are stored in `data/sql/`.
- `ddev dump-db ddev`: dump your main database table-wise.
- `ddev deploy-db ddev`: import your table-wise dump.
  - Remote SSH projects can be added as additional targets under `.ddev/commands/web/db-targets/` (see the README there).

### Claude Code in the web container
The Claude Code CLI is built into the web image (`.ddev/web-build/Dockerfile.claude-code`). Its state (login, settings) is kept per project in `.ddev/.claude-config/` (gitignored). On initialization, a `CLAUDE.md` / `AGENTS.md` with project instructions, a `.mcp.json` and project skills in `.claude/skills/` are copied into the project root.

**Skills:**
- `working-on-drupal-issues`: works on new or existing drupal.org issues through the drupalcode MCP server (see `DRUPALCODE_TOKEN` below). It creates issues from the Drupal template, implements the fix locally with tests, creates the issue fork, pushes the branch and opens the MR. Claude uses it on its own when asked to create or work on a Drupal issue, or call it with `/working-on-drupal-issues`.

**Updating the CLI:** Auto-updates are disabled inside the container. To get the latest version, rebuild the web image with `ddev debug rebuild` (your login and settings are kept). The VSCode extension is not affected by this; it updates through VSCode as usual.

### Personal settings (`.ddev/.env.web`)
Personal settings are configured in `.ddev/.env.web` (gitignored). Create it yourself with the variables you need; [`.ddev/.example.env.web`](.ddev/.example.env.web) lists all available ones. Run `ddev restart` (and `ddev code` again) after changing it. Note: DDEV only loads env files from `.ddev/`, not a `.env` in the project root.
- **Git identity** (`GIT_AUTHOR_*` / `GIT_COMMITTER_*`): used for commits inside the container (VSCode Source Control, terminal, Claude Code). Without it, commits are authored by "DDEV User".
- **`DRUPALCODE_TOKEN`**: enables the drupalcode GitLab MCP server (e.g. for creating issues on git.drupalcode.org):
  1. Create a **fine-grained** personal access token on git.drupalcode.org. The GitLab MCP server only accepts fine-grained tokens; *legacy* tokens are rejected with `403 insufficient_scope`, even with the `api` scope.
     1. Log in at [git.drupalcode.org](https://git.drupalcode.org) (with your drupal.org account).
     2. Click your avatar (upper-right corner) → **Edit profile**.
     3. In the left sidebar, go to **Access** → **Personal access tokens** (direct link: [git.drupalcode.org/-/user_settings/personal_access_tokens](https://git.drupalcode.org/-/user_settings/personal_access_tokens)).
     4. Open the **Generate token** dropdown and select **Fine-grained token** (not "Legacy token").
     5. Enter a **name** (e.g. `ddev-claude-mcp`) and optionally a description.
     6. Set an **expiration date** (max. 365 days; empty = 365 days from today). You will have to create a new token once it expires.
     7. Under **Group and project access**, select **All groups and projects I have access to**.
     8. In the **Resource and permission selector**, pick the **Resource access** tab, check a category, then check the resources and their permissions within it (or type the resource name into "Search for resources to add"). To read and create work items (issues) and merge requests, check:

        | Tab | Category | Resource | Permissions |
        |---|---|---|---|
        | **User** | Duo | MCP tool | **Execute** (required, otherwise the MCP server rejects the token) |
        | **Group and project** | Project Planning | Work Item | **Read**, **Create**, **Update** (Update is needed to comment, e.g. `/do:fork` to create the issue fork) |
        | **Group and project** | Project Planning | Label | **Read**, **Create**, **Update** (to set and manage labels on work items) |
        | **Group and project** | Repository | Merge Request | **Read**, **Create** (+ **Update** to edit merge requests) |
        | **Group and project** | Repository | Branch | **Read**, **Create** |
        | **Group and project** | Repository | Commit | **Read**, **Create** |
        | **Group and project** | Repository | Repository | **Read** (to read files and the repository tree) |
        | **Group and project** | Projects | Project | **Read** |

        The **Global** tab is not needed.
     9. Click **Generate token** and **copy the token immediately** (it starts with `glpat-`). It is shown only once; after leaving or reloading the page, you have to create a new one.
     10. Put it into `.ddev/.env.web`: `DRUPALCODE_TOKEN="glpat-..."`. Never commit it (the file is gitignored).
  2. Make sure `.mcp.json` exists in the project root (copied from `.ddev/initiation-additions/` on initialization).
  3. `ddev restart`, then `ddev code` and use the Claude extension normally.

### Delete the environment
1. `ddev delete -y` deletes the containers and unlists the project.
2. Delete the project folder.

### Further DDEV tools and add-ons
- https://github.com/ddev/awesome-ddev
- https://addons.ddev.com/

---

## FAQ / Troubleshooting

### *How do I install DDEV?*
- Install docker-ce inside Ubuntu / WSL: https://docs.docker.com/engine/install/ubuntu/#install-using-the-repository
- Add DDEV to apt and install it: https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/#debianubuntu
- Run `ddev -v` to check that it is installed correctly.

### *How do I update DDEV?*
If you installed DDEV via apt (as above), run `sudo apt update && sudo apt upgrade` inside your Ubuntu / WSL instance. For other installation methods, see the [DDEV upgrade docs](https://ddev.readthedocs.io/en/stable/users/install/ddev-upgrade/).

### *I can not execute the custom "ddev drowl-init" command*
- Make sure you have the newest DDEV and Docker version and try restarting Docker first.
- Make sure you do not have two DDEV projects with the same name (`ddev list`). If there was a project with the same name in the past that was not properly deleted using `ddev delete`, check your Docker containers and remove the old ones.
- Make sure your project name is valid (see [Project names](#quick-start)).
- If none of this helps, run `ddev start` first to see if that already throws any errors. Afterwards delete the project again using `ddev delete`, remove the folder and initialize it again.

### *I can not reach any URL from within the DDEV container*
This is probably related to internal DNS problems. [This](https://stackoverflow.com/questions/78853210/not-able-to-resolve-any-hostnames-inside-docker-container-using-wsl-2-docker-ce/78853229#78853229) might help.

---

## Other
**Special thanks to [Joachim](https://github.com/joachim-n) for creating https://github.com/joachim-n/drupal-core-development-project/, which helped us to make this project valid for work on Drupal core issues.**
