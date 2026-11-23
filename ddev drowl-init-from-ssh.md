# ddev drowl-init-from-ssh

Nov 20, 2026 · @Joshua Sedler

A new host command that builds a local DDEV development copy of an existing Drupal site straight from its server over SSH: composer files, custom code, database (incl. table prefix) and config. Files are served via stage\_file\_proxy. The DB dump is streamed, so no dump file ever lands on the server.

## Decisions so far

Scope is settled. These points come from the first draft round:

| Topic | Decision |
| --- | --- |
| Command | `.ddev/commands/host/drowl-init-from-ssh.sh`, a host command, so it uses the host's ssh/rsync and the user's key directly (no `ddev auth ssh`) |
| Files | stage\_file\_proxy only, no file download |
| Config | Not copied; the active config comes with the DB import |
| Dev tooling | Same dev stack as `drowl-init` (core-dev, drush, coder, phpstan rules, kint, devel, stage\_file\_proxy, initiation-additions, drupal-ci setup) |
| DB credentials | Read from the remote settings.php via remote PHP; the password never leaves the server |
| DB dump | Streamed over SSH, never written to the remote disk |
| `drowl-init-from-existing` | Stays untouched (issue #129 tracks it separately) |

## Usage and prompts

`ddev drowl-init-from-ssh [--cleanup-composer]`. All questions are asked up front, then the command runs unattended.

| Prompt | Default | Notes |
| --- | --- | --- |
| SSH target | – | `user@host` |
| SSH port | `22` |  |
| SSH key (not prompted) | The active key (ssh-agent / `~/.ssh/config`) | No `-i` is passed; ssh picks the key as usual |
| Remote project root | – | Folder holding `composer.json`, e.g. `/var/www/example.com` |
| Web directory | `web` | Relative to the project root; others use `root`, `docroot`, `public_html` |
| stage\_file\_proxy origin | – | e.g. `https://www.example.com` |
| "Not production ready" confirmation | – | Same y/n loop as `drowl-init-from-existing` |
| Personal settings | – | Existing `.ddev/drowl-init/ask-personal-settings.sh` |

SSH runs with connection multiplexing (`ControlMaster=auto`, `ControlPersist=10m`), so the key passphrase is asked once and rsync reuses the connection. The master connection is closed on exit.

## Flow

Eleven steps, run in this order after the prompts:

1. **Connect.** Open the multiplexed SSH master and test it with `ssh … true`. If that fails, abort with a clear message.
2. **Composer.** Fetch the remote `composer.json`, plus `composer.lock` if present. Also fetch the local patch files it references: relative paths in `extra.patches`, and `extra.patches-file` with its contents.
   - Default mode: use both files as-is, set `ddev config --docroot=<web dir>` (the remote installer-paths point there), then `ddev composer install`.
   - `--cleanup-composer`: see the section below.
3. **Read the remote settings.php.** A single `ssh … php` call gets its script via stdin, so nothing is written remotely. It defines `$app_root`, `$site_path`, `$settings`, `$databases`, includes `sites/default/settings.php` (plus its local/prod includes) and prints JSON: DB host, port, name, user, prefix, driver (no password), `file_public_path`.
   - Parsed locally with `jq`, which is checked up front.
   - Any driver drush can dump works, not just `mysql`.
     - `mysql` (MySQL and MariaDB): DDEV's default database
     - Any other driver (e.g. pgsql, `sqlite`): abort with a clear message naming it
   - Array prefix (deprecated): warn and use `prefix['default']`.
4. **Dev stack.** Same `composer require` calls as `drowl-init`. `drupal/core-dev` matches the remote core major version.
5. **Custom code.** rsync `<web>/modules/custom`, `<web>/themes/custom` and `<web>/profiles/custom` (whichever exist), excluding `node_modules` and `.git`.
6. **DB dump.** Streamed, see below.
7. **Start and import.** Run `ddev config` with `--disable-settings-management`, then `ddev get ddev/ddev-phpmyadmin` and `ddev start`, and copy `.ddev/initiation-additions/.`.
   - Set the remote prefix in the local `settings.php` (`'prefix' => ''` → the remote value, validated against `^[A-Za-z0-9_]*$`).
   - `ddev import-db --file=…`.
8. **Directories.** Create `files/private`, `files/translations` and `tmp`.
9. **Post-setup.** `drush updb -y` and `cr`, then enable devel and stage\_file\_proxy. Set the stage\_file\_proxy origin, kint as dumper, and verbose errors; turn off CSS/JS aggregation. Run the drupal-ci setup, then `drush uli` for login. Imported users' passwords stay unchanged.
10. **Template cleanup.** Remove `README.md`, `.git` and `.gitattributes`, as `drowl-init` does. Then `ddev describe` and the helper notes.
11. **To-do summary.** Print a closing checklist of manual follow-ups, plus any warnings collected during the run:
    - Take a look at the composer.json: production-only packages, and with --cleanup-composer the merged keys and versions that moved.
    - Check that all patches applied.
    - Compare the remote settings includes (e.g. Redis, Solr, environment settings) with the local settings.php.
    - Run drush cex if the config should be versioned in files/sync.
    - Private files were not copied.

A single `cleanup` trap (`EXIT INT TERM`) removes temp files and partial dumps, closes the SSH master, and on failure prints the step that failed.

## Database dump without orphan files

The dump is piped straight into the SSH channel, so the server never holds a dump file that could be left behind.

```bash
DUMP_PART="data/sql/remote-dump.sql.gz.part"
ssh "${SSH_OPTS[@]}" "$TARGET" \
  "bash -o pipefail -c 'cd \"$REMOTE_ROOT\" && vendor/bin/drush sql:dump \
     --structure-tables-list=cache,cache_*,sessions,watchdog | gzip -c'" \
  > "$DUMP_PART"
```

`drush sql:dump` writes to stdout when no `--result-file` is given, so it streams. It runs `mysqldump` for both MySQL and MariaDB. `vendor/bin/drush` must exist on the remote.

- **Nothing to clean up remotely.** If the connection drops or the user presses Ctrl-C, the remote dump gets SIGPIPE/SIGHUP and exits.
- **Password stays on the server.** Drush reads it from settings.php there and hands it to the dump tool itself (for MySQL via a short-lived credentials file); it never reaches the local machine.
- **Local integrity check.** `gzip -t` and the ssh exit status (pipefail) must pass, plus the `-- Dump completed` trailer for MySQL. Only then is `.part` renamed to `data/sql/remote-dump-<date>.sql.gz`. The trap deletes a leftover `.part`.
- **Low server impact.** Drush's MySQL dump already uses `--single-transaction` and `--opt` (which includes `--quick`). Cache, sessions and watchdog tables go structure-only.

## --cleanup-composer

**Terminal warning.** With the flag set, the command first prints a caution banner: only use `--cleanup-composer` if you know what you are doing, because it replaces the project's composer setup and can change package versions. A y/n confirmation follows, asked up front with the other prompts; "n" aborts before anything is changed.

With the flag, the project starts from our default `composer.json`. Only the dependency keys are taken over from the remote; the docroot is always `web`.

1. Build the default exactly like `drowl-init`: `composer create-project drupal/recommended-project`, then the allow-plugins, patches and asset-packagist `composer config` calls.
2. Merge with `jq`, replacing these keys with the remote values:

| Key | Taken from remote |
| --- | --- |
| `require` | Yes |
| `require-dev` | Yes |
| `extra.patches` | Yes |
| `extra.patches-file` | Yes, if set (file copied too) |
| `repositories` | Yes |
| `scripts`, `extra.drupal-scaffold`, `extra.installer-paths`, `config` | No, our defaults |

3. Drop the remote `composer.lock` and run `ddev composer update -W`. Versions may move within the remote constraints, so `drush updb -y` after the import matters here.

## Open questions for the team

- [ ] Large DBs: should we skip data of cache, sessions and watchdog tables (`--ignore-table` + structure-only), like `dump-db`'s `ignoredDbTablesPattern`? => YES
- [ ] Remote without PHP CLI or without mysqldump/mariadb-dump: is a fallback via remote `drush sql:dump` (also to stdout) worth it? => drush sql:dump is now always used
- [ ] Multisite: only sites/default for now. Later: a site prompt per run, or loop over all sites? => Show a notice if multisite detected to let the user know that default was chosen
- [ ] Should a non-interactive mode (all prompts as flags) come in the first version, e.g. for CI? => Yes
- [ ] Private files: ignore them (stage\_file\_proxy only handles public files), or optional rsync later? => Yes ignore them.
