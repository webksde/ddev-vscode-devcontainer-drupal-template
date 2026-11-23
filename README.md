# DDEV Drupal Template With Attached VSCode
The tools inside this repository will create a modified Drupal environment, focused on development and debugging.

**DO NOT USE IN PRODUCTION - This is a local development tool!**

## Quick-Start
**Spin up a ready-to-code Drupal 10/11 CMS DDEV based development container with preconfigured Drupal Best-Practice Dev-Modules and Visual Studio Code Settings/Extensions in three commands!** 🚀

Initialization takes about 5 minutes and is only needed once. Make sure you meet the [prerequisites](#prerequisites) first.

Start a **contrib** dev environment using Drupal 11, meant for testing and working on contrib modules / issues:
```
git clone https://github.com/webksde/ddev-vscode-devcontainer-drupal-template.git contrib-vscode-drupal && cd contrib-vscode-drupal && ddev drowl-init
```
Use `ddev drowl-init -v 10` to install Drupal 10 instead.

Or start a **core** dev environment using gitified Drupal 12.x-dev, meant for working on core issues:
```
git clone https://github.com/webksde/ddev-vscode-devcontainer-drupal-template.git core-vscode-drupal && cd core-vscode-drupal && ddev drowl-init-dev
```

Or create a local development copy of an **existing** Drupal site, straight from its server over SSH (see [below](#development-copy-of-an-existing-site-via-ssh)):
```
git clone https://github.com/webksde/ddev-vscode-devcontainer-drupal-template.git my-site && cd my-site && ddev drowl-init-from-ssh
```

Afterwards:
- Use `ddev describe` to check the status & URLs of your project.
- Log in with `admin` / `admin` (an additional user `max` / `max` is created as well).
- Use `ddev code` to open your prepared VSCode IDE attached to the web container. The recommended extensions (`.vscode/extensions.json`) are installed automatically on attach. If that does not work in your setup, go to "Extensions" in VSCode and search for `@recommended` to install them manually.

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
5. (Optional) To let Claude Code work on drupal.org issues and merge requests, generate a fine-grained personal access token on git.drupalcode.org **before** running `drowl-init*`, which asks for it at the start (see [Personal settings](#personal-settings-ddevenvweb) for the step-by-step guide). You can also skip it and add it to `.ddev/.env.web` later.

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
- All linters and PHPUnit behave like the drupal.org GitLab CI jobs, `ddev drupal-ci` runs them all at once (see [below](#linting-like-the-drupalorg-gitlab-ci))
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
- `ddev code`: attach VSCode to your running container. It first writes a Dev Containers "named container configuration" for the web container (from `.vscode/extensions.json`), so VSCode installs the recommended extensions automatically, also after the container was recreated. The file is regenerated on every run, so edit the recommendations in `.vscode/extensions.json` rather than in VSCode's "Open Named Container Configuration File".
- `ddev phpunit path/to/tests`: run tests using PHPUnit.
- `ddev phpunit-coverage path/to/cover`: create a test coverage report of the given directory.
- `ddev xdebug on`: turn on XDebug, then in VSCode go to "Run and Debug", start "Listen for XDebug" and open your project in the browser.

### Linting like the drupal.org GitLab CI
The linters behave like the validate jobs of the [drupal.org GitLab CI templates](https://git.drupalcode.org/project/gitlab_templates): every path is checked as part of its project (module, theme, profile or recipe), from the project's root, with the project's own config files (`phpcs.xml.dist`, `phpstan.neon`, `.eslintrc.json`, `.stylelintrc.json`, `.cspell.json`, `.cspell-project-words.txt`, ...), and the CI defaults where it has none. The JS tools come from core's `node_modules`, installed with yarn like in CI.
- `ddev drupal-ci web/modules/custom/my_module`: run all checks like the CI pipeline (phpcs, phpstan, eslint, stylelint, cspell and PHPUnit, which runs the project's `tests/` folder), with a summary at the end. `--fix` autofixes first (phpcbf, eslint / stylelint `--fix`), `--only=` / `--skip=` select tools, e.g. `--skip=phpunit` to only lint. Use the single commands below for tool options.
- `ddev phpcs web/modules/custom/my_module`: check PHP code against the Drupal coding standards (`ddev phpcbf ...` to autofix).
- `ddev phpstan web/modules/custom/my_module`: static analysis with PHPStan.
- `ddev eslint web/modules/custom/my_module (--fix)`: lint / autofix JavaScript and YAML.
- `ddev stylelint web/modules/custom/my_module (--fix)`: lint / autofix CSS.
- `ddev cspell web/modules/custom/my_module`: check spelling (`--words-only --unique` lists only the unknown words).
- `ddev drupal-ci-update`: update the CI default configs (`phpcs.xml.dist`, `phpstan.neon`, `.cspell.json`) and helper scripts in `.ddev/drupal-ci/` to the newest gitlab_templates 1.x release (`1.x-latest`, like contrib projects use), and refresh the linter setup (see below).

Paths can also be single files, or folders containing several projects (e.g. `web/modules/custom`). Without a path, the current directory is checked. Inside the container (VSCode terminal, Claude Code), the same runners are available as `.ddev/drupal-ci/bin/<tool>` (incl. `drupal-ci` and `phpunit`); the VSCode extensions use the same configs.

`drowl-init` sets up the linters once (`.ddev/drupal-ci/setup.sh`): it downloads the CI default configs, installs core's node modules, provides core's configs site-wide as the fallback for projects without their own (`web/.eslintrc.json` extending core's `.eslintrc.passing.json` like CI, excluded from core's scaffolding in `composer.json`; `.stylelintrc.json` and `.prettierrc.json` in the project root), and generates the `cspell.json` for the VSCode CSpell extension (CI defaults plus each custom and contrib project's `.cspell-project-words.txt`). Run `ddev drupal-ci-update` to refresh all of it, e.g. after adding a project with its own `.cspell-project-words.txt`.

### Development copy of an existing site via SSH
`ddev drowl-init-from-ssh [--cleanup-composer] [--no-sanitize]` builds a local development copy of an existing Drupal site from its server. Like `drowl-init`, run it once in a freshly cloned copy of this repository.

**Requirements:** `ssh`, `rsync`, `jq` and `gzip` on the host, PHP CLI, `rsync` and `vendor/bin/drush` on the remote. It is a host command, so it uses your host's SSH setup (ssh-agent, `~/.ssh/config`). The connection is opened once and reused, so you enter a passphrase / password only once. Nothing is written on the server.

All questions are asked up front: SSH target and port (default `22`), remote project root (the folder with the `composer.json`), web directory (default `web`), stage_file_proxy origin, a confirmation and the personal settings. Then it runs unattended:
1. **Connects** and checks the remote. It uses the remote PHP version and the remote database type and version locally. If DDEV does not ship that database version, it uses the next higher one that DDEV supports.
2. **Composer:** fetches `composer.json` / `composer.lock`, plus the local patches, patches files, scaffold files and path repositories they reference, and runs `composer install`. The remote web directory is kept.
3. **Reads the remote `settings.php`** (table prefix, public files path). The database password never leaves the server. Multisites are not supported: only the default site (`sites/default/settings.php` and its database) is copied. The other sites are ignored, with a warning, if the remote has further `sites/*/settings.php` files or a `sites/sites.php` with entries.
4. **Adds the dev stack** of `drowl-init`: core-dev in the remote core version, drush, coder, phpstan rules, kint, devel and stage_file_proxy. Packages the project already requires are left as they are.
5. **Copies the custom modules, themes and profiles** via rsync (without `node_modules` and `.git`).
6. **Streams the database dump** (`drush sql:dump`) over SSH into a temp file (with `--no-sanitize`: `data/sql/remote-dump-<date>.sql.gz`), then checks that it is complete. The tables `cache`, `cache_*`, `sessions`, `watchdog`, `flood`, `queue`, `semaphore`, `batch` and `key_value_expire` (with the table prefix, if there is one) are dumped without data, only their structure. So no session tokens, logs or pending production jobs (mails, newsletters, API syncs) are copied.
7. **Sets up DDEV:** adds phpMyAdmin and copies the initiation additions (VSCode settings, `phpunit.xml`, `settings.php`, ...). If the web directory is not `web`, it rewrites their `web/` paths. Then it sets the table prefix and public files path in `settings.php` and imports the dump. As the database comes from production, the local `settings.php` disables `automated_cron` and the `smtp` module and sets all Search API indexes read-only, so the copy does not send mails or write to the production search server. Then it **sanitizes the database** (`drush sql:sanitize`: user emails, passwords, sessions) and removes the raw dump. Enabled modules that may use production credentials from the database (SMTP / mailer, Solr, S3, payment, newsletter, CRM, ...) are listed as warnings.
8. **Post-setup:** runs `drush updb`, enables devel and stage_file_proxy and sets up the linters. The stage_file_proxy origin is added at the end of the local `settings.php` (`$config['stage_file_proxy.settings']['origin']`), so it stays set after a config import, and also saved in the database. With `drush config-set`, it sets Kint as the devel dumper, disables CSS / JS aggregation and enables verbose errors. These changes are saved in the database only. The copied `settings.local.php` (included by `settings.php`) also forces verbose errors and disables the render, page and dynamic page caches. Public files are fetched from the origin on demand. Private files are not copied.
9. **Saves a dump** in `data/sql/db-complete-dump.sql.gz` for `ddev drowl-reset-db`, and removes `README.md` and `.git` like `drowl-init`.
10. **Prints** a to-do list, all warnings of the run and a login link. Log in with the site's own users, as no `admin` / `max` users are created.

**`--cleanup-composer`** replaces the remote `composer.json` with a clean one, based on our default:
- Taken over from the remote: `require`, `require-dev`, patches, repositories and allowed plugins.
- Everything else comes from our default `composer.json`. The web directory is always `web`.
- The remote `composer.lock` is not used. Instead, `composer update -W` installs the newest package versions the constraints allow, so they can differ from the remote.

Only use it if you know what you are doing.

**`--no-sanitize`** keeps the personal data of the imported database and keeps the raw dump in `data/sql/`. Only use it if you need the real user data for debugging. Either way, the database is a copy of production data: `data/sql/` is only readable by you, never use `ddev share` or `bind_all_interfaces` with it, and remove it with `ddev delete -Oy` (plus `data/sql/`) when you are done. Sanitization only covers users: other personal data (webform submissions, orders, profile fields) stays in the database.

For CI, pass all values as options and add `--non-interactive`. This skips all prompts, confirmations and personal settings, and fails instead of asking for an SSH password. `--ssh-target`, `--remote-root` and `--origin` are required, `--ssh-port` (default `22`) and `--web-dir` (default `web`) are optional: `ddev drowl-init-from-ssh --non-interactive --ssh-target=user@example.com --ssh-port=22 --remote-root=/var/www/example.com --web-dir=web --origin=https://www.example.com`.

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
Personal settings are configured in `.ddev/.env.web` (gitignored). The `drowl-init*` commands ask for your git name, git mail and `DRUPALCODE_TOKEN` at the start and write them into this file (leave a value empty to skip it; existing values are offered as defaults). You can also create or edit it yourself; [`.ddev/.example.env.web`](.ddev/.example.env.web) lists all available ones. Run `ddev restart` (and `ddev code` again) after changing it. Note: DDEV only loads env files from `.ddev/`, not a `.env` in the project root.
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
        | **User** | System Access | User | **Read** (to look up your own user, e.g. for assigning issues and MRs to you) |
        | **Grop and project** | Project Planning | Work Item | **Read**, **Create**, **Update** (Update is needed to comment, e.g. `/do:fork` to create the issue fork) |
        | **Group and project** | Project Planning | Label | **Read**, **Create**, **Update** (to set and manage labels on work items) |
        | **Group and project** | Repository | Merge Request | **Read**, **Create** (+ **Update** to edit merge requests) |
        | **Group and project** | Repository | Branch | **Read**, **Create** |
        | **Group and project** | Repository | Commit | **Read**, **Create** |
        | **Group and project** | Repository | Repository | **Read** (to read files and the repository tree) |
        | **Group and project** | Repository | Code | **Push** (to push commits to branches) |
        | **Group and project** | Projects | Project | **Read** |

     9. Click **Generate token** and **copy the token immediately**. It is shown only once; after leaving or reloading the page, you have to create a new one.
     10. Put it into `.ddev/.env.web`: `DRUPALCODE_TOKEN="your-fine-grained-token"`. Never commit it (the file is gitignored).
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
