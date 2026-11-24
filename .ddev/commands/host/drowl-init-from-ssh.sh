#!/usr/bin/env bash

## Description: Builds a local DDEV development copy of an existing Drupal site from its server over SSH (composer files, custom code, database). Public files can be served via stage_file_proxy.
## Usage: drowl-init-from-ssh [flags]
## Example: "ddev drowl-init-from-ssh", "ddev drowl-init-from-ssh --cleanup-composer", "ddev drowl-init-from-ssh --non-interactive --ssh=user@example.com:2222 --remote-root=/var/www/example.com --origin=https://www.example.com"
## Flags: [{"Name":"cleanup-composer","Usage":"Start from our default composer.json and only take over the dependency keys from the remote (only use it if you know what you are doing)"},{"Name":"ssh","Type":"string","Usage":"SSH address user@host[:port], e.g. user@example.com or user@example.com:2222, the port defaults to 22 (required with --non-interactive)"},{"Name":"remote-root","Type":"string","Usage":"Remote project root holding the composer.json, absolute or relative to the remote home, e.g. /var/www/example.com or ~/example.com (required with --non-interactive)"},{"Name":"web-dir","Type":"string","Usage":"Web directory, relative to the project root (default: web)"},{"Name":"origin","Type":"string","Usage":"Remote site URL, used as the stage_file_proxy origin, e.g. https://www.example.com (optional, leave it out to skip the stage_file_proxy setup)"},{"Name":"non-interactive","Usage":"Never prompt: confirmations are skipped, personal settings are skipped, --ssh and --remote-root are required"},{"Name":"no-sanitize","Usage":"Keep the personal data of the imported database (by default, it is sanitized with drush sql:sanitize and the raw remote dump is not kept)"}]

# The "## Flags:" line above is parsed by DDEV, which only accepts it as a
# single line of JSON. DDEV generates "ddev drowl-init-from-ssh --help" from
# it. Readable version (keep both in sync):
#   --cleanup-composer   Start from our default composer.json, only take over the
#                        dependency keys from the remote
#   --ssh                SSH address user@host[:port], e.g. user@example.com
#                        or user@example.com:2222 (port default: 22) (*)
#   --remote-root        Remote project root holding the composer.json, absolute
#                        or relative to the remote home (~/example.com) (*)
#   --web-dir            Web directory, relative to the project root (default: web)
#   --origin             Remote site URL, used as the stage_file_proxy origin,
#                        e.g. https://www.example.com (empty: skip stage_file_proxy)
#   --non-interactive    Never prompt: skip confirmations, missing values fail,
#                        skip personal settings
#   --no-sanitize        Keep the personal data (user emails, passwords) of the
#                        imported database and keep the raw remote dump
# (*) Required with --non-interactive. Without it, missing values are asked.

# Builds a local DDEV development copy of an existing Drupal site straight from
# its server over SSH:
# - composer.json / composer.lock (plus the local files they reference, e.g.
#   patches) and the custom modules, themes and profiles,
# - the database (incl. table prefix), streamed over SSH, so no dump file ever
#   lands on the server,
# - the active config comes with the database, public files are served via
#   stage_file_proxy, private files are not copied,
# - the imported database is sanitized (drush sql:sanitize) unless
#   --no-sanitize is given, and integrations that would act on production
#   services (cron, SMTP, Search API) are disabled in the local settings.php.
# On top of that, the same dev stack as "drowl-init" is set up.
#
# This is a host command, so it uses the host's ssh / rsync and the user's SSH
# key (ssh-agent / ~/.ssh/config) directly.
#
# Note: Keep this script compatible with bash 3.2 (macOS).

set -eo pipefail

# shellcheck source=../../drowl-init/lib.sh
source .ddev/drowl-init/lib.sh

# ------------------------------------------------------------------------------
# Configuration (lists, maintained here, the shared ones are in
# .ddev/drowl-init/steps.sh)
# ------------------------------------------------------------------------------

# Commands required on the host:
REQUIRED_HOST_COMMANDS=(ssh rsync jq gzip ddev sed)

# Drupal modules added to "require-dev" (enabled in the post-setup step):
COMPOSER_DEV_MODULES=(drupal/devel drupal/stage_file_proxy)
# Folders fetched from the remote web directory: DROWL_CUSTOM_CODE_DIRS.
# Never fetched via rsync:
RSYNC_EXCLUDES=(--exclude=node_modules --exclude=.git)

# Tables dumped without data (only the structure), their data is not needed
# locally:
# - cache, cache_*: rebuilt anyway,
# - sessions: session tokens of the production users,
# - watchdog, flood: logs, IP addresses,
# - queue: pending production jobs (mails, newsletters, API syncs), which
#   would otherwise be processed by the local cron,
# - semaphore, batch, key_value_expire: locks, running batches, temp stores.
# (Quoted, so "cache_*" is not expanded as a glob by the local shell.)
STRUCTURE_ONLY_TABLES=(cache 'cache_*' sessions watchdog flood queue semaphore batch key_value_expire)

# Enabled modules that talk to external (production) services with the
# credentials from the production database are reported as a warning. Machine
# name patterns (regex, anchored), by what they could do:
RISKY_MODULE_PATTERNS=(
  # Mails (API based mailers are not caught by Mailpit):
  smtp symfony_mailer symfony_mailer_lite phpmailer_smtp mailsystem swiftmailer
  sendgrid_integration mailgun mandrill postmark sparkpost amazon_ses mailjet
  # Newsletters / marketing / CRM:
  'mailchimp.*' simplenews sendinblue brevo cleverreach salesforce 'hubspot.*'
  # SMS / push notifications:
  sms smsframework twilio push_notifications onesignal firebase
  # Search servers:
  search_api_solr search_api_opensearch elasticsearch_connector search_api_algolia
  search_api_typesense acquia_search search_api_pantheon
  # Purging the production CDN / reverse proxy cache:
  purge 'purge_.*' varnish_purge acquia_purge cloudflare fastly akamai
  # Remote file storage, backups to remote destinations:
  s3fs 'flysystem.*' backup_migrate
  # Payments:
  commerce_payment 'commerce_.*_gateway' 'commerce_(stripe|paypal|braintree|mollie|square|authnet)'
  # Paid APIs (AI, machine translation):
  ai 'ai_provider_.*' openai 'tmgmt.*'
  # Webhooks, imports, content sharing:
  webhooks feeds entity_share_server
  # Queues / cron:
  ultimate_cron queue_ui advancedqueue
  # Analytics (local page views end up in the production project):
  'posthog.*'
  # Stored credentials (API keys used by other modules):
  key encrypt real_aes
)

# ------------------------------------------------------------------------------
# Helpers (output, prompts and error handling: .ddev/drowl-init/ui.sh)
# ------------------------------------------------------------------------------

# Quotes a value for a remote POSIX shell, as ssh passes the command as one
# string to the remote shell: wraps it in single quotes and escapes contained
# single quotes ('it'\''s'). E.g. a remote root with spaces stays one argument.
rq() {
  printf "'%s'" "$(printf '%s' "$1" | sed "s/'/'\\\\''/g")"
}

# ------------------------------------------------------------------------------
# Arguments
# ------------------------------------------------------------------------------

CLEANUP_COMPOSER=0
NON_INTERACTIVE=0
SANITIZE=1
SSH_ADDRESS=""
REMOTE_ROOT=""
REMOTE_WEB=""
ORIGIN=""

set_option() {
  case "$1" in
  --ssh) SSH_ADDRESS="$2" ;;
  --remote-root) REMOTE_ROOT="$2" ;;
  --web-dir) REMOTE_WEB="$2" ;;
  --origin) ORIGIN="$2" ;;
  esac
}

while [ $# -gt 0 ]; do
  case "$1" in
  --cleanup-composer) CLEANUP_COMPOSER=1 ;;
  --non-interactive) NON_INTERACTIVE=1 ;;
  --no-sanitize) SANITIZE=0 ;;
  --ssh=* | --remote-root=* | --web-dir=* | --origin=*)
    set_option "${1%%=*}" "${1#*=}"
    ;;
  --ssh | --remote-root | --web-dir | --origin)
    [ $# -ge 2 ] || die "Missing value for $1."
    set_option "$1" "$2"
    shift
    ;;
  *)
    die "Unknown argument '$1'. See 'ddev drowl-init-from-ssh --help'."
    ;;
  esac
  shift
done

# ------------------------------------------------------------------------------
# Cleanup trap
# ------------------------------------------------------------------------------

TMP_DIR=""
# The ssh destination (user@host) and the port, split from --ssh below:
SSH_TARGET=""
SSH_PORT=""
SSH_OPTS=()
SSH_STARTED=0
DUMP_PART=""

# Removes temp files and partial dumps and closes the SSH master connection
# (called by drowl_cleanup, which also names the failed step):
drowl_cleanup_hook() {
  if [ -n "$DUMP_PART" ] && [ -f "$DUMP_PART" ]; then
    rm -f "$DUMP_PART"
  fi
  if [ "$SSH_STARTED" = 1 ]; then
    ssh "${SSH_OPTS[@]}" -O exit "$SSH_TARGET" >/dev/null 2>&1
  fi
  if [ -n "$TMP_DIR" ] && [ -d "$TMP_DIR" ]; then
    rm -rf "$TMP_DIR"
  fi
}

# ------------------------------------------------------------------------------
# Preconditions
# ------------------------------------------------------------------------------

drowl_require_host_commands "${REQUIRED_HOST_COMMANDS[@]}"
drowl_require_fresh_template

drowl_start "Initialising a development copy of an existing Drupal site over SSH!"

# ------------------------------------------------------------------------------
# Prompts (all asked up front, afterwards the command runs unattended)
# ------------------------------------------------------------------------------

if [ "$CLEANUP_COMPOSER" = 1 ]; then
  printf '\n%s%sCAUTION: --cleanup-composer%s %sOnly use it if you know what you are doing!\n' "$C_BOLD" "$C_RED" "$C_RESET" "$C_RED"
  printf 'It replaces the composer setup with our default composer.json (only require,\n'
  printf 'require-dev, patches and repositories are taken over from the remote) and\n'
  printf 'drops the remote composer.lock, so package versions can change.%s\n' "$C_RESET"
  confirm_or_abort "Continue with --cleanup-composer?"
fi

# Each answer is checked right away (against the remote, where possible), so
# a typo can be fixed at once, before anything is changed locally.

# SSH with connection multiplexing, so the key passphrase is asked once and
# rsync reuses the connection. Short socket path (unix socket path limit):
TMP_DIR="$(mktemp -d /tmp/drowl-ssh.XXXXXX)"

# The PATH of a remote login shell, set after connecting (see below), every
# remote command then runs with it:
REMOTE_PATH=""

# Runs a command (a single string, run by the remote shell) on the remote:
rssh() {
  if [ -n "$REMOTE_PATH" ]; then
    ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "PATH=$(rq "$REMOTE_PATH"); export PATH; $*"
  else
    ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "$*"
  fi
}

# Checks the remote project in a single SSH call, before anything is changed
# locally. The script is sent via stdin ("sh -s", nothing is written on the
# remote) with the project root as $1 and the web directory as $2. It prints a
# "key=value" line per found item (missing items print nothing) and always
# exits with 0, so a missing item is not mistaken for an SSH error:
cat >"$TMP_DIR/probe.sh" <<'SH'
cd "$1" 2>/dev/null || { echo "root=0"; exit 0; }
echo "root=1"
[ -f composer.json ] && echo "composer_json=1"
[ -f composer.lock ] && echo "composer_lock=1"
[ -x vendor/bin/drush ] && echo "drush=1"
[ -n "$2" ] || exit 0
[ -d "$2" ] && echo "web=1"
[ -f "$2/sites/default/settings.php" ] && echo "settings=1"
# Multisite: further sites with a settings.php, or a sites.php with entries.
# (An unmatched glob stays literal, so it is skipped like default/simpletest.)
for f in "$2"/sites/*/settings.php; do
  case "$f" in
  "$2/sites/default/settings.php" | "$2/sites/simpletest/settings.php" | "$2/sites/*/settings.php") ;;
  *) echo "multisite=1" ;;
  esac
done
[ -f "$2/sites/sites.php" ] && grep -Eq '^[[:space:]]*\$sites\[' "$2/sites/sites.php" && echo "multisite=1"
command -v php >/dev/null 2>&1 && echo "php=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
command -v rsync >/dev/null 2>&1 && echo "rsync=1"
command -v gzip >/dev/null 2>&1 && echo "gzip=1"
{ command -v mysqldump || command -v mariadb-dump; } >/dev/null 2>&1 && echo "mysqldump=1"
exit 0
SH
PROBE=""
# Runs the probe script with the current REMOTE_ROOT and REMOTE_WEB:
run_probe() {
  PROBE="$(rssh "sh -s -- $(rq "$REMOTE_ROOT") $(rq "$REMOTE_WEB")" <"$TMP_DIR/probe.sh")"
}

# Prints the value of the given key from the probe output, e.g. "probe php":
probe() {
  printf '%s\n' "$PROBE" | sed -n "s/^$1=//p" | tail -n 1
}

# SSH address: split "user@host[:port]" into the ssh destination and the port
# (default: 22), then connect. A bare host (no "user@") is accepted as well,
# ssh then uses the User from ~/.ssh/config or the local username. It is not
# advertised, as the local username rarely matches the remote one:
while true; do
  ask SSH_ADDRESS "SSH address" "user@host[:port], e.g. user@example.com:2222" "" "ssh" "" "default port: 22"
  SSH_TARGET="$SSH_ADDRESS"
  SSH_PORT=22
  case "$SSH_TARGET" in
  *:*:*)
    reject SSH_ADDRESS "Invalid SSH address '$SSH_ADDRESS'. IPv6 addresses are not supported, use a host name instead."
    continue
    ;;
  *:*)
    SSH_PORT="${SSH_TARGET##*:}"
    SSH_TARGET="${SSH_TARGET%:*}"
    if [[ ! "$SSH_PORT" =~ ^[0-9]{1,5}$ ]] || [ "$SSH_PORT" -lt 1 ] || [ "$SSH_PORT" -gt 65535 ]; then
      reject SSH_ADDRESS "Invalid SSH port '$SSH_PORT' in '$SSH_ADDRESS'."
      continue
    fi
    ;;
  esac
  case "$SSH_TARGET" in
  "" | @* | *@ | *[[:space:]]* | -*)
    reject SSH_ADDRESS "Invalid SSH address '$SSH_ADDRESS'. Use user@host[:port], e.g. user@example.com:2222."
    continue
    ;;
  esac
  # Shown in messages:
  SSH_DISPLAY="${SSH_TARGET}:${SSH_PORT}"
  SSH_OPTS=(-p "$SSH_PORT" -o ConnectTimeout=20 -o ControlMaster=auto -o "ControlPath=$TMP_DIR/cm-%C" -o ControlPersist=10m -o ServerAliveInterval=30)
  if [ "$NON_INTERACTIVE" = 1 ]; then
    SSH_OPTS+=(-o BatchMode=yes)
  fi
  info "Connecting to ${SSH_DISPLAY}..."
  if rssh true </dev/null; then
    SSH_STARTED=1
    break
  fi
  reject SSH_ADDRESS "Could not connect to '${SSH_DISPLAY}'. Check the address, the port and your SSH key (ssh-agent / ~/.ssh/config)."
done
success "Connected."

# Read the PATH of a remote bash login shell, as a terminal session (e.g.
# PuTTY) gets it ("bash -l" reads /etc/profile, ~/.bash_profile / ~/.profile).
# "ssh host cmd" runs cmd in a non-login, non-interactive shell, which does not
# read the profile files. So on e.g. Plesk, where PHP lives in
# /opt/plesk/php/X.Y/bin and is only added to the PATH in the profile, "php"
# (and so "vendor/bin/drush") is not found. Only the PATH is taken over,
# instead of running every command in a login shell, as profiles may print
# text (which would end up in the composer.json or the database dump). So the
# PATH is printed behind the marker "LOGIN_SHELL_PATH:" and only that line is
# read:
# Extra rsync options, set once the remote login PATH is known:
RSYNC_OPTS=()
REMOTE_PATH="$(rssh "bash -lc $(rq 'printf "LOGIN_SHELL_PATH:%s\n" "$PATH"')" </dev/null 2>/dev/null | sed -n 's/^LOGIN_SHELL_PATH://p' | tail -n 1 || true)"
if [ -n "$REMOTE_PATH" ]; then
  # rsync starts "rsync --server" on the remote through the same non-login
  # shell, so give it the PATH as well:
  RSYNC_OPTS=(--rsync-path="PATH=$(rq "$REMOTE_PATH"); export PATH; rsync")
else
  warn "Could not read the PATH of a remote login shell (bash -l), using the default PATH of non-interactive SSH commands."
fi

# Remote project root: must exist and hold a composer.json and drush.
while true; do
  ask REMOTE_ROOT "Remote project root" "folder of the composer.json, e.g. /var/www/example.com or ~/example.com" "" "remote-root"
  [ "$REMOTE_ROOT" != "/" ] && REMOTE_ROOT="${REMOTE_ROOT%/}"
  # Shown in messages as entered, e.g. "~/example.com":
  REMOTE_ROOT_DISPLAY="$REMOTE_ROOT"
  # All remote paths are quoted, so the remote shell never expands "~".
  # Relative paths are relative to the remote home for ssh and rsync, so
  # "~/path" is the same as "path" and "~" is the same as ".":
  case "$REMOTE_ROOT" in
  \~) REMOTE_ROOT="." ;;
  \~/*) REMOTE_ROOT="${REMOTE_ROOT#\~/}" ;;
  \~*)
    reject REMOTE_ROOT "Invalid remote project root '$REMOTE_ROOT'. '~user' paths are not supported, use an absolute path instead."
    continue
    ;;
  esac
  run_probe
  if [ "$(probe root)" != 1 ]; then
    reject REMOTE_ROOT "The remote project root '${REMOTE_ROOT_DISPLAY}' does not exist."
  elif [ "$(probe composer_json)" != 1 ]; then
    reject REMOTE_ROOT "No composer.json found in '${REMOTE_ROOT_DISPLAY}'."
  elif [ "$(probe drush)" != 1 ]; then
    reject REMOTE_ROOT "No 'vendor/bin/drush' found in '${REMOTE_ROOT_DISPLAY}'. It is required to read the settings and dump the database."
  else
    break
  fi
done

# Web directory: relative to the project root, must hold the
# sites/default/settings.php.
while true; do
  ask REMOTE_WEB "Web directory" "relative to the project root" "web" "web-dir"
  REMOTE_WEB="${REMOTE_WEB#./}"
  REMOTE_WEB="${REMOTE_WEB%/}"
  if [ -z "$REMOTE_WEB" ] || [[ "$REMOTE_WEB" == /* ]] || [[ "/$REMOTE_WEB/" == */../* ]] || [[ ! "$REMOTE_WEB" =~ ^[A-Za-z0-9._/-]+$ ]]; then
    reject REMOTE_WEB "Invalid web directory '$REMOTE_WEB'. It must be relative to the project root, e.g. 'web'."
    continue
  fi
  run_probe
  if [ "$(probe web)" != 1 ]; then
    reject REMOTE_WEB "The web directory '${REMOTE_WEB}' does not exist in '${REMOTE_ROOT_DISPLAY}'."
  elif [ "$(probe settings)" != 1 ]; then
    reject REMOTE_WEB "No '${REMOTE_WEB}/sites/default/settings.php' found in '${REMOTE_ROOT_DISPLAY}'."
  else
    break
  fi
done

# Remote site URL (optional): written into settings.php inside single quotes,
# so quotes and backslashes are not allowed. Empty skips the stage_file_proxy
# setup. If curl is available, the URL must answer (any HTTP status, e.g. a
# 403 behind basic auth, counts). Entering an unreachable URL a second time
# accepts it anyway (e.g. only reachable via VPN):
ORIGIN_PATTERN="^https?://[^[:space:]'\"\\\\]+\$"
ORIGIN_UNREACHABLE=""
while true; do
  ask ORIGIN "Remote site URL" "for stage_file_proxy, e.g. https://www.example.com, empty to skip" "" "origin" optional
  [ -n "$ORIGIN" ] || break
  if [[ ! "$ORIGIN" =~ $ORIGIN_PATTERN ]]; then
    reject ORIGIN "Invalid remote site URL '$ORIGIN'. It must start with http:// or https:// and must not contain spaces, quotes or backslashes."
    continue
  fi
  ORIGIN="${ORIGIN%/}"
  if ! command -v curl >/dev/null 2>&1 || [ "$ORIGIN" = "$ORIGIN_UNREACHABLE" ] || curl -sS -o /dev/null --max-time 15 -L "$ORIGIN"; then
    break
  fi
  if [ "$NON_INTERACTIVE" = 1 ]; then
    warn "Could not reach '${ORIGIN}', stage_file_proxy is set up with it anyway."
    break
  fi
  ORIGIN_UNREACHABLE="$ORIGIN"
  reject ORIGIN "Could not reach '${ORIGIN}'. Enter another URL, leave it empty to skip, or enter the same URL again to use it anyway."
done

if [ "$NON_INTERACTIVE" != 1 ]; then
  printf '\n%sThis will NOT create a production ready copy of your website, but a modified\n' "$C_YELLOW"
  printf 'environment focused on development and debugging. %sDO NOT PUSH IT BACK TO PRODUCTION!%s\n' "$C_BOLD" "$C_RESET"
fi
confirm_or_abort "Continue anyway?"

# Ask for the personal settings (git identity, DRUPALCODE_TOKEN), written to
# .ddev/.env.web before the containers start:
drowl_ask_personal_settings

# The local docroot: the remote one, except with --cleanup-composer, where our
# default composer.json (installer-paths) always uses "web":
if [ "$CLEANUP_COMPOSER" = 1 ]; then
  LOCAL_WEB="web"
else
  LOCAL_WEB="$REMOTE_WEB"
fi

R_ROOT="$(rq "$REMOTE_ROOT")"
R_WEB="$(rq "$REMOTE_WEB")"

# rsync takes the ssh command as a single string (-e), using the same options
# reuses the multiplexed connection:
RSYNC_RSH="ssh ${SSH_OPTS[*]}"
# Overall progress for the larger transfers (custom code), on a terminal and
# with rsync >= 3.1 only (the rsync / openrsync shipped with macOS lacks
# --info):
RSYNC_PROGRESS=()
if [ -t 1 ] && rsync --info=progress2 --version >/dev/null 2>&1; then
  RSYNC_PROGRESS=(--info=progress2)
fi

printf '\n%sAlright! Initialising your project from %s:%s. This will take a while...%s\n' "$C_GREEN" "$SSH_DISPLAY" "$REMOTE_ROOT_DISPLAY" "$C_RESET"

# ------------------------------------------------------------------------------
# Check the remote
# ------------------------------------------------------------------------------

step "Checking the remote ${SSH_DISPLAY}"

# The project root and the web directory were checked after their prompts,
# check the remote tools now:
run_probe
[ -n "$(probe php)" ] || die "No PHP CLI found on the remote. It is required to run drush."
[ "$(probe rsync)" = 1 ] || die "rsync is missing on the remote."
[ "$(probe gzip)" = 1 ] || die "gzip is missing on the remote."
[ "$(probe mysqldump)" = 1 ] || die "Neither mysqldump nor mariadb-dump was found on the remote. It is required by 'drush sql:dump'."

# Use the remote PHP version, if DDEV supports it:
REMOTE_PHP="$(probe php)"
PHP_FLAG=()
case "$REMOTE_PHP" in
5.6 | 7.[0-4] | 8.[0-5])
  PHP_FLAG=(--php-version="$REMOTE_PHP")
  ;;
*)
  warn "The remote PHP version '${REMOTE_PHP}' is not supported by DDEV, keeping the PHP version from .ddev/config.yaml."
  ;;
esac

# The remote Drupal and database server version (via drush, non-fatal):
REMOTE_DRUPAL_VERSION="$(rssh "cd $R_ROOT && vendor/bin/drush status --field=drupal-version" </dev/null 2>/dev/null | tr -d '[:space:]' || true)"
# The database version is read through Drupal's own database connection
# (PDO), as "drush sql:query" needs the mysql / mariadb client on the remote,
# which is often missing or fails (e.g. newer MariaDB clients enforce SSL).
# Prints e.g. "MariaDB 10.11.6" or "MySQL 8.0.36":
read -r -d '' READ_DB_VERSION_PHP <<'PHP' || true
$connection = \Drupal::database();
$type = $connection->databaseType();
if ($type === 'mysql') {
  $type = method_exists($connection, 'isMariaDb') && $connection->isMariaDb() ? 'MariaDB' : 'MySQL';
}
echo 'DROWL_DB_VERSION:' . $type . ' ' . $connection->version() . PHP_EOL;
PHP
REMOTE_DB_VERSION="$(rssh "cd $R_ROOT && vendor/bin/drush php:eval $(rq "$READ_DB_VERSION_PHP")" </dev/null 2>/dev/null | sed -n 's/^DROWL_DB_VERSION://p' | tail -n 1 || true)"
# Fallback: the mysql / mariadb client via drush:
if [ -z "$REMOTE_DB_VERSION" ]; then
  REMOTE_DB_VERSION="$(rssh "cd $R_ROOT && vendor/bin/drush sql:query 'SELECT VERSION()'" </dev/null 2>/dev/null | tail -n 1 | tr -d '[:space:]' || true)"
fi

# The local database always comes from .ddev/config.yaml, the remote version
# is only displayed for comparison.
# Prints a key of the "database:" block in .ddev/config.yaml, e.g.
# "config_db version" (quotes stripped):
config_db() {
  awk -v key="$1:" '
    /^database:/ { in_db = 1; next }
    in_db && /^[^[:space:]#]/ { in_db = 0 }
    in_db && $1 == key { gsub(/["\047]/, "", $2); print $2; exit }
  ' .ddev/config.yaml 2>/dev/null
}
LOCAL_DB_TYPE="$(config_db type)"
LOCAL_DB_VERSION="$(config_db version)"

# The local PHP version: the remote one if DDEV supports it (see above),
# otherwise the one from .ddev/config.yaml:
if [ ${#PHP_FLAG[@]} -gt 0 ]; then
  LOCAL_PHP="$REMOTE_PHP"
else
  LOCAL_PHP="$(awk '$1 == "php_version:" { gsub(/["\047]/, "", $2); print $2; exit }' .ddev/config.yaml 2>/dev/null)"
fi

# (The Drupal core version is shown in step 2, locally it comes from the
# remote composer files.)
note "Remote: PHP ${REMOTE_PHP}, database ${REMOTE_DB_VERSION:-unknown}"
note "Local:  PHP ${LOCAL_PHP:-unknown}, database ${LOCAL_DB_TYPE:-unknown} ${LOCAL_DB_VERSION:-unknown} (from .ddev/config.yaml)"

# ------------------------------------------------------------------------------
# Composer
# ------------------------------------------------------------------------------

step "Composer"
DROWL_LOCAL_CHANGED=1

# Keeps the given paths, which are relative and inside the project root:
filter_local_paths() {
  local p
  while IFS= read -r p; do
    p="${p#./}"
    [ -n "$p" ] || continue
    case "/$p/" in
    */../*)
      warn "Not fetching '$p', which is outside of the project root."
      continue
      ;;
    esac
    printf '%s\n' "$p"
  done
}

# Fetches the given paths (files or folders, one per line in the given file)
# from the remote project root into the local project root. Missing ones are
# reported as warnings.
fetch_paths() {
  local list="$1" existing="$TMP_DIR/existing.txt" p
  [ -s "$list" ] || return 0
  # 1. Ask the remote which of the paths exist: $list is sent via stdin, the
  #    remote prints every path that exists.
  # 2. Do not trust that answer: keep only the lines that exactly match a line
  #    of $list. $list is our own list, already cleaned by filter_local_paths
  #    (no "..", see the callers). So a compromised remote can not add other
  #    paths (e.g. "../../.bashrc") to the rsync file list below:
  rssh "cd $R_ROOT && while IFS= read -r f; do [ -e \"\$f\" ] && printf '%s\n' \"\$f\"; done; exit 0" <"$list" >"$existing.raw"
  grep -Fx -f "$list" -- "$existing.raw" >"$existing" || true
  while IFS= read -r p; do
    grep -Fxq -- "$p" "$existing" || warn "'$p' is referenced by the remote composer setup, but does not exist on the remote."
  done <"$list"
  [ -s "$existing" ] || return 0
  # --files-from keeps the relative paths, "-r" is needed for folders (it is
  # not implied by "-a" together with --files-from):
  rsync -a -r "${RSYNC_OPTS[@]}" --files-from="$existing" "${RSYNC_EXCLUDES[@]}" -e "$RSYNC_RSH" "$SSH_TARGET:$REMOTE_ROOT/" ./
}

# Lists the local files a composer.json / patches file references: patches,
# the patches-file, scaffold file-mapping sources and path repositories:
# "p" walks a patches definition and returns the patch paths / URLs. It covers
# composer-patches 1 ({"package": {"description": "path"}}) and 2 (arrays,
# objects with "url"). Path repository URLs are cut before the first wildcard
# ("packages/*" -> "packages"). URLs and absolute paths are dropped, as they
# are not fetched from the remote project:
JQ_LOCAL_PATHS='
def p: if type == "string" then .
  elif type == "array" then (.[] | p)
  elif type == "object" then (if has("url") then .url else (.[] | p) end)
  else empty end;
[
  (.extra.patches // {} | p),
  (.patches // {} | p),
  (.extra["patches-file"] // empty),
  (.extra["drupal-scaffold"]["file-mapping"] // {} | .[] | if type == "object" then (.path // empty) else . end),
  (.repositories // [] | .[] | select(type == "object" and .type == "path") | .url | strings | sub("/?[^/]*[*?\\[{].*$"; ""))
]
| map(select(type == "string" and . != "" and (test("^[A-Za-z][A-Za-z0-9+.-]*://") | not) and (startswith("/") | not)))
| unique | .[]'

# Fetch the remote composer files:
REMOTE_COMPOSER_DIR="$TMP_DIR/remote-composer"
mkdir -p "$REMOTE_COMPOSER_DIR"
rssh "cat $R_ROOT/composer.json" </dev/null >"$REMOTE_COMPOSER_DIR/composer.json"
jq -e 'type == "object"' "$REMOTE_COMPOSER_DIR/composer.json" >/dev/null || die "The remote composer.json is not valid JSON."
if [ "$(probe composer_lock)" = 1 ]; then
  rssh "cat $R_ROOT/composer.lock" </dev/null >"$REMOTE_COMPOSER_DIR/composer.lock"
fi

# The remote Drupal core version (locked version first, then drush):
CORE_VERSION=""
if [ -f "$REMOTE_COMPOSER_DIR/composer.lock" ]; then
  CORE_VERSION="$(jq -r '[.packages[]? | select(.name == "drupal/core") | .version][0] // empty' "$REMOTE_COMPOSER_DIR/composer.lock")"
fi
CORE_VERSION="${CORE_VERSION:-$REMOTE_DRUPAL_VERSION}"
CORE_MAJOR="${CORE_VERSION%%.*}"
if [[ ! "$CORE_MAJOR" =~ ^[0-9]+$ ]]; then
  warn "Could not detect the remote Drupal core version, assuming Drupal 11."
  CORE_VERSION=""
  CORE_MAJOR=11
fi
info "Remote Drupal core: ${CORE_VERSION:-unknown} (major version ${CORE_MAJOR})."

# Fetch the local files referenced by the remote composer.json (patches, the
# patches-file, ...). composer-patches 2 also reads patches.json and
# patches.lock.json by default:
FETCH_RAW="$TMP_DIR/fetch-raw.txt"
FETCH_LIST="$TMP_DIR/fetch.txt"
{
  jq -r "$JQ_LOCAL_PATHS" "$REMOTE_COMPOSER_DIR/composer.json"
  if [ "$(jq -r '.extra["patches-file"] // empty' "$REMOTE_COMPOSER_DIR/composer.json")" = "" ]; then
    rssh "cd $R_ROOT && [ -f patches.json ] && echo patches.json; exit 0" </dev/null
  fi
  if [ "$CLEANUP_COMPOSER" != 1 ]; then
    rssh "cd $R_ROOT && [ -f patches.lock.json ] && echo patches.lock.json; exit 0" </dev/null
  fi
} >"$FETCH_RAW"
# (No pipe out of filter_local_paths, so its warnings are collected.)
filter_local_paths <"$FETCH_RAW" >"$TMP_DIR/fetch-filtered.txt"
sort -u "$TMP_DIR/fetch-filtered.txt" >"$FETCH_LIST"
fetch_paths "$FETCH_LIST"

# The patches files reference further local patches:
: >"$FETCH_RAW"
for patches_file in "$(jq -r '.extra["patches-file"] // empty' "$REMOTE_COMPOSER_DIR/composer.json")" patches.json; do
  if [ -n "$patches_file" ] && [ -f "$patches_file" ]; then
    jq -r "$JQ_LOCAL_PATHS" "$patches_file" >>"$FETCH_RAW" || warn "Could not parse the patches file '$patches_file'."
  fi
done
filter_local_paths <"$FETCH_RAW" >"$TMP_DIR/fetch-filtered.txt"
sort -u "$TMP_DIR/fetch-filtered.txt" >"$FETCH_LIST"
fetch_paths "$FETCH_LIST"

# Create the config.yaml and start the containers (needed for composer):
mkdir -p "./$LOCAL_WEB"
ddev config --project-type=drupal --docroot="$LOCAL_WEB" --webserver-type="apache-fpm" --composer-version="stable" "${PHP_FLAG[@]}" --disable-settings-management --auto
ddev start

if [ "$CLEANUP_COMPOSER" != 1 ]; then
  # Use the remote composer files as they are. The remote installer-paths point
  # to the remote web directory, which is the local docroot as well:
  cp "$REMOTE_COMPOSER_DIR/composer.json" ./composer.json
  if [ -f "$REMOTE_COMPOSER_DIR/composer.lock" ]; then
    cp "$REMOTE_COMPOSER_DIR/composer.lock" ./composer.lock
  else
    warn "The remote has no composer.lock, so 'composer install' resolved new package versions."
  fi
  ddev composer install --no-interaction
# Cleanup composer case:
else
  # Build our default composer.json exactly like drowl-init, but without
  # installing it:
  info "Building the default composer.json (drupal/recommended-project:^${CORE_MAJOR})..."
  ddev exec rm -rf /tmp/drowl-recommended-project
  ddev exec --raw composer create-project --no-install --no-scripts --no-interaction "drupal/recommended-project:^${CORE_MAJOR}" /tmp/drowl-recommended-project
  ddev exec --raw cp /tmp/drowl-recommended-project/composer.json /var/www/html/composer.json
  ddev exec rm -rf /tmp/drowl-recommended-project

  drowl_composer_allow_plugins "${DROWL_COMPOSER_ALLOWED_PLUGINS[@]}"
  drowl_composer_defaults "$LOCAL_WEB"

  # Take over the dependency keys from the remote. The remote allow-plugins
  # are added as well (ours win), otherwise the update fails on any plugin we
  # do not allow by default:
  jq --slurpfile remote "$REMOTE_COMPOSER_DIR/composer.json" '
    $remote[0] as $r
    | .require = ($r.require // {})
    | .["require-dev"] = ($r["require-dev"] // {})
    | if $r.extra.patches != null then .extra.patches = $r.extra.patches else del(.extra.patches) end
    | if $r.extra["patches-file"] != null then .extra["patches-file"] = $r.extra["patches-file"] else del(.extra["patches-file"]) end
    | if $r.repositories != null then .repositories = $r.repositories else . end
    | if ($r.config["allow-plugins"] | type) == "object"
      then .config["allow-plugins"] = ($r.config["allow-plugins"] + (.config["allow-plugins"] // {}))
      else . end
  ' composer.json >"$TMP_DIR/composer.merged.json"
  mv "$TMP_DIR/composer.merged.json" composer.json
  if [ "$(jq -r '.repositories == null' "$REMOTE_COMPOSER_DIR/composer.json")" = "true" ]; then
    warn "The remote composer.json has no repositories, kept our default repositories."
  fi

  # Drop the remote composer.lock, versions may move within the remote
  # constraints:
  rm -f composer.lock
  ddev composer update -W --no-interaction
fi

# ------------------------------------------------------------------------------
# Read the remote settings.php
# ------------------------------------------------------------------------------

step "Reading the remote settings.php"

# Read the values through a full Drupal bootstrap (drush php:eval), so all
# includes and conditions of the remote settings.php apply. The database
# password is never printed. The result is marked with "DROWL_JSON:", so any
# other output (notices, echo in the settings.php) is ignored:
read -r -d '' READ_SETTINGS_PHP <<'PHP' || true
$db = \Drupal\Core\Database\Database::getConnectionInfo()['default'] ?? [];
$prefix = $db['prefix'] ?? '';
$prefixArray = is_array($prefix);
if ($prefixArray) {
  $prefix = $prefix['default'] ?? '';
}
echo 'DROWL_JSON:' . json_encode([
  'driver' => (string) ($db['driver'] ?? ''),
  'host' => (string) ($db['host'] ?? ''),
  'database' => (string) ($db['database'] ?? ''),
  'username' => (string) ($db['username'] ?? ''),
  'prefix' => (string) $prefix,
  'prefix_array' => $prefixArray,
  'file_public_path' => (string) \Drupal\Core\Site\Settings::get('file_public_path', 'sites/default/files'),
], JSON_UNESCAPED_SLASHES) . "\n";
PHP
SETTINGS_OUTPUT="$(rssh "cd $R_ROOT && vendor/bin/drush php:eval $(rq "$READ_SETTINGS_PHP")" </dev/null)" || die "Reading the remote settings via 'drush php:eval' failed."

SETTINGS_JSON="$(printf '%s\n' "$SETTINGS_OUTPUT" | sed -n 's/^DROWL_JSON://p' | tail -n 1)"
[ -n "$SETTINGS_JSON" ] || die "Reading the remote settings failed (no result). Output was: ${SETTINGS_OUTPUT}"

setting() {
  printf '%s' "$SETTINGS_JSON" | jq -r ".$1 // empty"
}

DB_DRIVER="$(setting driver)"
if [ "$DB_DRIVER" != "mysql" ]; then
  die "The remote database driver is '${DB_DRIVER:-unknown}'. Only 'mysql' (MySQL / MariaDB) is supported."
fi

DB_PREFIX="$(setting prefix)"
if [ "$(setting prefix_array)" = "true" ]; then
  warn "The remote uses an array as table prefix (deprecated), using prefix['default'] ('${DB_PREFIX}')."
fi
[[ "$DB_PREFIX" =~ ^[A-Za-z0-9_]*$ ]] || die "Invalid remote table prefix '${DB_PREFIX}'."

FILE_PUBLIC_PATH="$(setting file_public_path)"

if [ "$(probe multisite)" = 1 ]; then
  warn "The remote is a multisite. Only sites/default was used (settings, database, custom code of the default site)."
fi

info "Remote database: driver $(setting driver), host $(setting host), database $(setting database), user $(setting username), prefix '${DB_PREFIX}'."
info "Remote public files path: ${FILE_PUBLIC_PATH}"

# ------------------------------------------------------------------------------
# Dev stack
# ------------------------------------------------------------------------------

step "Dev stack"

# Allow our composer plugins and add our general dependencies (packages the
# project already requires are left as they are):
drowl_composer_base

# Add the development modules. They go to require-dev, as this composer.json
# belongs to a production site:
drowl_composer_require --dev "${COMPOSER_DEV_MODULES[@]}"

# Add DEV dependencies. "drupal/core-dev" (phpunit, phpstan, ...) must match
# the core version exactly, so prefer the remote core version, which keeps
# drupal/core where it is:
if [ -n "$(drowl_composer_missing require drupal/core-dev)" ]; then
  if [ "$CLEANUP_COMPOSER" != 1 ] && [[ "$CORE_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    if ! ddev composer require --dev "drupal/core-dev:${CORE_VERSION}" -W --no-audit --no-interaction; then
      warn "Requiring drupal/core-dev:${CORE_VERSION} failed, used drupal/core-dev:^${CORE_MAJOR} instead, which may have updated drupal/core."
      ddev composer require --dev "drupal/core-dev:^${CORE_MAJOR}" -W --no-audit --no-interaction
    fi
  else
    ddev composer require --dev "drupal/core-dev:^${CORE_MAJOR}" -W --no-audit --no-interaction
  fi
fi
drowl_composer_dev_packages

# ------------------------------------------------------------------------------
# Custom code
# ------------------------------------------------------------------------------

step "Custom code"

for dir in "${DROWL_CUSTOM_CODE_DIRS[@]}"; do
  if rssh "test -d $R_ROOT/$R_WEB/$dir" </dev/null; then
    info "Fetching ${REMOTE_WEB}/${dir}..."
    mkdir -p "./$LOCAL_WEB/$dir"
    rsync -a "${RSYNC_OPTS[@]}" "${RSYNC_PROGRESS[@]}" "${RSYNC_EXCLUDES[@]}" -e "$RSYNC_RSH" "$SSH_TARGET:$REMOTE_ROOT/$REMOTE_WEB/$dir/" "./$LOCAL_WEB/$dir/"
  else
    info "No ${REMOTE_WEB}/${dir} on the remote, skipping."
  fi
done

# ------------------------------------------------------------------------------
# Database dump
# ------------------------------------------------------------------------------

step "Dumping the remote database (streamed, this can take a while)"

mkdir -p ./data/sql
# The dumps hold production data, only the current user may read them:
chmod 0700 ./data/sql

# Only the structure of STRUCTURE_ONLY_TABLES is dumped. drush drops entries
# matching no table, so list them with and without the prefix:
STRUCTURE_TABLES=""
for table in "${STRUCTURE_ONLY_TABLES[@]}"; do
  STRUCTURE_TABLES="${STRUCTURE_TABLES:+$STRUCTURE_TABLES,}${table}"
  if [ -n "$DB_PREFIX" ]; then
    STRUCTURE_TABLES="${STRUCTURE_TABLES},${DB_PREFIX}${table}"
  fi
done

# The dump is piped straight into the SSH channel, so the server never holds a
# dump file that could be left behind. If the connection drops, the remote dump
# gets SIGPIPE / SIGHUP and exits:
# The raw (unsanitized) dump is only kept with --no-sanitize. Otherwise it goes
# to the temp folder (mode 0700), which is removed at the end, also on errors:
if [ "$SANITIZE" = 1 ]; then
  DUMP_DIR="$TMP_DIR"
else
  DUMP_DIR="data/sql"
fi
DUMP_PART="$DUMP_DIR/remote-dump.sql.gz.part"
DUMP_FILE="$DUMP_DIR/remote-dump-$(date +%Y-%m-%d_%H-%M-%S).sql.gz"
REMOTE_DUMP_CMD="cd $R_ROOT && vendor/bin/drush sql:dump $(rq "--structure-tables-list=$STRUCTURE_TABLES") | gzip -c"
(
  umask 077
  : >"$DUMP_PART"
)
start_progress "$DUMP_PART" "Downloaded (gzipped)"
DUMP_STATUS=0
rssh "bash -o pipefail -c $(rq "$REMOTE_DUMP_CMD")" </dev/null >>"$DUMP_PART" || DUMP_STATUS=$?
stop_progress
[ "$DUMP_STATUS" = 0 ] || die "The remote database dump failed."

# Local integrity check, only then the .part file is renamed. Decompressing the
# whole file fails on corrupt data, and mysqldump ends every complete dump with
# a "-- Dump completed" line, so a dump that was cut off is detected (one pass,
# as it can take a while for large dumps):
info "Checking the dump..."
DUMP_TAIL="$(gzip -dc "$DUMP_PART" | tail -n 3)" || die "The database dump is corrupt (gzip failed)."
[[ "$DUMP_TAIL" == *"-- Dump completed"* ]] || die "The database dump is incomplete (no '-- Dump completed' trailer)."
mv "$DUMP_PART" "$DUMP_FILE"
DUMP_PART=""
if [ "$SANITIZE" = 1 ]; then
  success "Database dumped ($(du -h "$DUMP_FILE" | cut -f1)), to a temp file that is removed after the import and sanitization."
else
  success "Database dumped to ${DUMP_FILE} ($(du -h "$DUMP_FILE" | cut -f1)). It holds unsanitized production data!"
fi

# ------------------------------------------------------------------------------
# Start and import
# ------------------------------------------------------------------------------

step "Starting DDEV and importing the database"

drowl_add_ddev_addons ddev/ddev-phpmyadmin
# The containers are already running (for composer), restart them to load the
# add-on:
ddev restart

drowl_copy_initiation_additions "$LOCAL_WEB"
SETTINGS_PHP="./$LOCAL_WEB/sites/default/settings.php"

# Applies a sed expression to the local settings.php. It is used below to
# replace the default "prefix" and "file_public_path" lines of the template
# settings.php. Works through a temp file, as "sed -i" differs between
# GNU/Linux and macOS:
# Usage: replace_in_settings 'SED_EXPRESSION'
replace_in_settings() {
  sed "$1" "$SETTINGS_PHP" >"$TMP_DIR/settings.php"
  cat "$TMP_DIR/settings.php" >"$SETTINGS_PHP"
}

# Set the remote table prefix (validated above):
if [ -n "$DB_PREFIX" ]; then
  grep -q "^  'prefix' => '',\$" "$SETTINGS_PHP" || die "Could not find the database prefix line in ${SETTINGS_PHP}."
  replace_in_settings "s/^  'prefix' => '',\$/  'prefix' => '${DB_PREFIX}',/"
  info "Set the table prefix '${DB_PREFIX}' in ${SETTINGS_PHP}."
fi

# Use the remote public files path, so stage_file_proxy fetches the same paths:
if [ "$FILE_PUBLIC_PATH" != "sites/default/files" ]; then
  if [[ "$FILE_PUBLIC_PATH" =~ ^[A-Za-z0-9_.-][A-Za-z0-9_./-]*$ ]] && [[ "/$FILE_PUBLIC_PATH/" != */../* ]]; then
    replace_in_settings "s|^\$settings\['file_public_path'\] = 'sites/default/files';\$|\$settings['file_public_path'] = '${FILE_PUBLIC_PATH%/}';|"
    info "Set the public files path '${FILE_PUBLIC_PATH}' in ${SETTINGS_PHP}."
  else
    warn "The remote public files path is '${FILE_PUBLIC_PATH}', which can not be used locally. Kept 'sites/default/files', so stage_file_proxy may not find the files."
  fi
fi

# Set the stage_file_proxy origin in settings.php, so it stays set even if the
# config is imported (the "drush config-set" below only sets it in the
# database). The origin was validated above (no quotes or backslashes):
if [ -n "$ORIGIN" ]; then
  {
    printf '\n// Development copy (drowl-init-from-ssh): fetch missing public files from\n'
    printf '// the remote site.\n'
    printf "\$config['stage_file_proxy.settings']['origin'] = '%s';\n" "$ORIGIN"
  } >>"$SETTINGS_PHP"
  info "Set the stage_file_proxy origin '${ORIGIN}' in ${SETTINGS_PHP}."
fi
{
  printf '\n// Development copy (drowl-init-from-ssh): the database comes from\n'
  printf '// production, so do not let this copy act on production services:\n'
  printf '// - no cron on page requests (it would process mails, syncs, ...),\n'
  printf "\$config['automated_cron.settings']['interval'] = 0;\n"
  printf '// - no mails via the smtp module (PHP mail() is caught by Mailpit).\n'
  printf "\$config['smtp.settings']['smtp_on'] = FALSE;\n"
} >>"$SETTINGS_PHP"
info "Disabled automated_cron and the smtp module in ${SETTINGS_PHP}."

ddev import-db --file="$DUMP_FILE"

# Search API indexes would write to (or clear) the production search server
# (e.g. Solr), so they are set to read-only in settings.php:
SEARCH_INDEXES="$(ddev drush php:eval "echo 'DROWL_INDEXES:' . implode(',', \\Drupal::configFactory()->listAll('search_api.index.')) . PHP_EOL;" 2>/dev/null | sed -n 's/^DROWL_INDEXES://p' | tail -n 1 || true)"
if [ -n "$SEARCH_INDEXES" ]; then
  {
    printf '// - Search API indexes are read-only (no indexing to / clearing of the\n'
    printf '//   production search server):\n'
  } >>"$SETTINGS_PHP"
  for index in ${SEARCH_INDEXES//,/ }; do
    [[ "$index" =~ ^search_api\.index\.[A-Za-z0-9_]+$ ]] || continue
    printf "\$config['%s']['read_only'] = TRUE;\n" "$index" >>"$SETTINGS_PHP"
  done
  info "Set the Search API indexes read-only in ${SETTINGS_PHP}: ${SEARCH_INDEXES}"
  warn "Search API indexes are read-only in ${SETTINGS_PHP}. Point the search servers to a local backend before re-enabling indexing."
fi

# Sanitize the personal data (user emails and passwords, sessions) and remove
# the raw dump:
if [ "$SANITIZE" = 1 ]; then
  info "Sanitizing the database (drush sql:sanitize)..."
  # Users with the administrator role keep their email and password, so the
  # site's admins can still log in as usual:
  ddev drush sql:sanitize --ignored-roles=administrator -y || die "'drush sql:sanitize' failed. The database still holds unsanitized production data, delete the project with 'ddev delete -Oy'."
  rm -f "$DUMP_FILE"
  success "Sanitized the database, the raw remote dump was removed."
else
  warn "--no-sanitize: the local database and ${DUMP_FILE} hold unsanitized production data (user emails, password hashes, ...)."
fi

# Warn about enabled modules that talk to external (production) services with
# the credentials from the production database (see RISKY_MODULE_PATTERNS):
RISKY_MODULE_REGEX="^($(
  IFS='|'
  printf '%s' "${RISKY_MODULE_PATTERNS[*]}"
))\$"
RISKY_MODULES="$(ddev drush pm:list --status=enabled --type=module --format=json 2>/dev/null | jq -r --arg re "$RISKY_MODULE_REGEX" 'keys[] | select(test($re))' 2>/dev/null | tr '\n' ' ' || true)"
if [ -n "$RISKY_MODULES" ]; then
  warn "These enabled modules may use production credentials / services from the database: ${RISKY_MODULES}. Check their config (and override it in ${SETTINGS_PHP}) before running cron or using them."
fi

# ------------------------------------------------------------------------------
# Directories
# ------------------------------------------------------------------------------

step "Directories"

drowl_create_directories "$LOCAL_WEB"
success "Created the custom code and project directories (files/private, files/sync, files/translations, tmp)."

# ------------------------------------------------------------------------------
# Post-setup
# ------------------------------------------------------------------------------

step "Post-setup"

if ! ddev drush updb -y; then
  warn "'drush updb' failed. Run 'ddev drush updb' again and check the output."
fi
ddev drush cr

# Activate the development modules (stage_file_proxy only with an origin):
if [ -n "$ORIGIN" ]; then
  ddev drush en devel stage_file_proxy -y
  ddev drush config-set stage_file_proxy.settings origin "$ORIGIN" -y
else
  ddev drush en devel -y
fi

# Activate kint as default devel variables dumper:
ddev drush config-set devel.settings devel_dumper kint -y

drowl_dev_settings
drowl_setup_linters

# The "normal" dump, used by "ddev drowl-reset-db":
drowl_export_db ./data/sql/db-complete-dump.sql.gz

# ------------------------------------------------------------------------------
# Template cleanup
# ------------------------------------------------------------------------------

step "Template cleanup"

drowl_remove_template_files

# ------------------------------------------------------------------------------
# Summary
# ------------------------------------------------------------------------------

todo "Take a look at the composer.json!"
if [ "$CLEANUP_COMPOSER" = 1 ]; then
  todo "--cleanup-composer: check the merged keys (require, require-dev, patches, repositories; the remote allow-plugins were added) and the package versions that moved (compare with the remote composer.lock)."
fi
todo "Check that all patches applied (see the composer output above)."
todo "The local ${SETTINGS_PHP} comes from our template, not from the remote. Only the table prefix, the public files path and the stage_file_proxy origin were taken over. Compare it with the remote settings.php and its includes, and copy over what the site needs locally (e.g. config_sync_directory, file_private_path, \$config overrides, custom \$settings, Redis, Solr, environment-specific includes)."
todo "Run \"ddev drush cex\" if the config should be versioned in files/sync."
if [ -n "$ORIGIN" ]; then
  todo "The stage_file_proxy origin (${ORIGIN}) is set at the end of ${SETTINGS_PHP}. Change it there if the public files should come from another site."
  todo "Private files were not copied (stage_file_proxy only serves public files)."
else
  todo "No remote site URL was given, so stage_file_proxy was not set up and no files were copied. Copy the public files manually, or enable stage_file_proxy and set its origin at the end of ${SETTINGS_PHP} (\$config['stage_file_proxy.settings']['origin'])."
fi
if [ "$SANITIZE" = 1 ]; then
  todo "The database was sanitized (user emails and passwords, except for users with the administrator role). Other personal data (e.g. webform submissions, orders, profile fields) is still in it."
fi
todo "The database holds production data: never use \"ddev share\" or bind_all_interfaces. When done, remove it with \"ddev delete -Oy\" and delete data/sql."
todo "This is NOT a production ready copy. DO NOT PUSH IT BACK TO PRODUCTION!"

if [ "$SANITIZE" != 1 ]; then
  DROWL_SUMMARY_COMMANDS+=("ddev import-db --file=${DUMP_FILE}|Import the original (unsanitized) remote database dump again.")
fi
drowl_print_summary "$LOCAL_WEB"
