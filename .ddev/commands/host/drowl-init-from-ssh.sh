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

# ------------------------------------------------------------------------------
# Configuration (lists, maintained here)
# ------------------------------------------------------------------------------

# Commands required on the host:
REQUIRED_HOST_COMMANDS=(ssh rsync jq gzip ddev sed)

# Composer plugins to allow (unless the remote composer.json configures them):
COMPOSER_ALLOWED_PLUGINS=(
  cweagans/composer-patches oomphinc/composer-installers-extender
  szeidler/composer-patches-cli tbachert/spi symfony/runtime
  dealerdirect/phpcodesniffer-composer-installer phpstan/extension-installer
  php-http/discovery
)
# Packages added to "require" (unless already present):
COMPOSER_PACKAGES=(cweagans/composer-patches szeidler/composer-patches-cli oomphinc/composer-installers-extender)
# Drupal modules added to "require-dev" (enabled in the post-setup step):
COMPOSER_DEV_MODULES=(drupal/devel drupal/stage_file_proxy)
# Further packages added to "require-dev". drupal/core-dev is handled
# separately, as it has to match the core version:
COMPOSER_DEV_PACKAGES=(drush/drush drupal/coder:^8 phpstan/phpstan-deprecation-rules kint-php/kint squizlabs/php_codesniffer:^3)

# Folders fetched from the remote web directory (and always created locally):
CUSTOM_CODE_DIRS=(modules/custom themes/custom profiles/custom)
# Never fetched via rsync:
RSYNC_EXCLUDES=(--exclude=node_modules --exclude=.git)

# Copied initiation additions with "web/" paths, which are pointed to the local
# docroot if it differs:
DOCROOT_REWRITE_FILES=(.vscode/settings.json phpunit.xml jsconfig.json AGENTS.md)

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
# Output helpers
# ------------------------------------------------------------------------------

WARNINGS=()
NOTES=()

info() {
  printf '\e[36m%s\e[0m\n' "$*"
}

# Prints an info and collects it for the summary at the end (like warn(), but
# for facts worth repeating, e.g. the remote and local versions):
note() {
  info "$@"
  NOTES+=("$*")
}

success() {
  printf '\e[32m%s\e[0m\n' "$*"
}

# Prints a warning and collects it for the "Warnings" section of the summary
# at the end:
warn() {
  printf '\e[33mWARNING: %s\e[0m\n' "$*" >&2
  WARNINGS+=("$*")
}

die() {
  printf '\n\e[31mERROR: %s\e[0m\n' "$*" >&2
  exit 1
}

# Progress line for a file that grows while it is downloaded: shows the size so
# far, the elapsed time and the average rate, redrawn every second on stderr.
# The final size is unknown (the dump is streamed), so there is no percentage.
# Only on a terminal, so logs (CI, redirected output) stay clean. Stopped with
# stop_progress (and by cleanup() on errors / Ctrl-C):
# Usage: start_progress FILE "Label"
PROGRESS_PID=""
start_progress() {
  [ -t 2 ] || return 0
  local file="$1" label="$2"
  (
    # stop_progress waits for the running awk / sleep, so nothing is printed
    # after the line was cleared:
    trap 'exit 0' TERM
    start=$SECONDS
    # Stops on its own, if the script is gone:
    while kill -0 "$$" 2>/dev/null; do
      bytes="$(wc -c <"$file" 2>/dev/null | tr -d ' ')"
      elapsed=$((SECONDS - start))
      awk -v label="$label" -v b="${bytes:-0}" -v e="$elapsed" '
        function human(x,  u, i) {
          split("B KB MB GB TB", u, " ")
          for (i = 1; x >= 1024 && i < 5; i++) x /= 1024
          return sprintf(i == 1 ? "%d %s" : "%.1f %s", x, u[i])
        }
        BEGIN {
          printf "\r\033[K\033[36m%s: %s (%02d:%02d, %s/s)\033[0m", label, human(b), e / 60, e % 60, human(e > 0 ? b / e : 0)
        }' >&2
      sleep 1
    done
  ) &
  PROGRESS_PID=$!
}

stop_progress() {
  [ -n "$PROGRESS_PID" ] || return 0
  kill "$PROGRESS_PID" 2>/dev/null || true
  wait "$PROGRESS_PID" 2>/dev/null || true
  PROGRESS_PID=""
  # Clear the progress line:
  printf '\r\e[K' >&2
}

CURRENT_STEP="Preparation"
step() {
  CURRENT_STEP="$1"
  printf '\n\e[1;32m==> %s\e[0m\n' "$1"
}

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
# Set once the first local file is written, so a failure hints how to start
# over:
LOCAL_CHANGED=0

# Removes temp files and partial dumps, closes the SSH master connection and
# names the failed step:
cleanup() {
  local status="${1:-0}"
  trap - EXIT INT TERM
  set +e
  stop_progress
  if [ -n "$DUMP_PART" ] && [ -f "$DUMP_PART" ]; then
    rm -f "$DUMP_PART"
  fi
  if [ "$SSH_STARTED" = 1 ]; then
    ssh "${SSH_OPTS[@]}" -O exit "$SSH_TARGET" >/dev/null 2>&1
  fi
  if [ -n "$TMP_DIR" ] && [ -d "$TMP_DIR" ]; then
    rm -rf "$TMP_DIR"
  fi
  if [ "$status" -ne 0 ]; then
    printf '\n\e[31mdrowl-init-from-ssh failed at step "%s" (exit code %s).\e[0m\n' "$CURRENT_STEP" "$status" >&2
    if [ "$LOCAL_CHANGED" = 1 ]; then
      printf '\e[31mThe project is half initialised now. Start over from a fresh clone of the template (run "ddev delete -Oy" in this folder first).\e[0m\n' >&2
    fi
  fi
  exit "$status"
}
# EXIT passes the script's exit code. On Ctrl-C (INT) / kill (TERM), $? is not
# meaningful, so the usual shell codes are passed (128 + signal number):
trap 'cleanup $?' EXIT
trap 'cleanup 130' INT
trap 'cleanup 143' TERM

# ------------------------------------------------------------------------------
# Preconditions
# ------------------------------------------------------------------------------

for cmd in "${REQUIRED_HOST_COMMANDS[@]}"; do
  command -v "$cmd" >/dev/null 2>&1 || die "'$cmd' is required on the host, but was not found. Please install it first."
done

# ------------------------------------------------------------------------------
# Prompts (all asked up front, afterwards the command runs unattended)
# ------------------------------------------------------------------------------

# Asks for a value, unless it was given as an option. With "optional", an
# empty value is accepted.
# Usage: ask VARIABLE "Prompt" [default] [option-name] [optional]
ask() {
  local var="$1" prompt="$2" default="$3" option="$4" optional="$5" value
  value="${!var}"
  if [ -z "$value" ]; then
    if [ "$NON_INTERACTIVE" = 1 ]; then
      [ -n "$default" ] || [ "$optional" = optional ] || die "Missing value for --${option} (required in non-interactive mode)."
      value="$default"
    else
      while true; do
        read -r -p $'\e[36m'"${prompt}${default:+ [$default]}: "$'\e[0m' value
        value="${value:-$default}"
        [ -n "$value" ] || [ "$optional" = optional ] && break
      done
    fi
  fi
  printf -v "$var" '%s' "$value"
}

# Asks a y/n question, like drowl-init-from-existing. "n" aborts. Skipped in
# non-interactive mode.
confirm_or_abort() {
  local question="$1" answer
  # Non-interactive mode skips all confirmations:
  if [ "$NON_INTERACTIVE" = 1 ]; then
    return 0
  fi
  while true; do
    read -r -p "$question"$'\n' answer
    case ${answer:0:1} in
    y | Y)
      return 0
      ;;
    n | N)
      printf '\n\e[31mAborting...\n\e[0m\n'
      # Nothing was changed yet:
      exit 0
      ;;
    *)
      printf '\n\e[33mI do not understand. Please repeat that.\n\e[0m\n'
      ;;
    esac
  done
}

if [ "$CLEANUP_COMPOSER" = 1 ]; then
  printf '\n\e[1;31m!!! CAUTION: --cleanup-composer !!!\e[0m\n'
  printf '\e[31mOnly use --cleanup-composer if you know what you are doing! It replaces the\n'
  printf 'project'\''s composer setup with our default composer.json (only require,\n'
  printf 'require-dev, patches and repositories are taken over from the remote) and\n'
  printf 'drops the remote composer.lock, so package versions can change.\e[0m\n'
  confirm_or_abort $'\n\e[33mContinue with --cleanup-composer (y/n)?\e[0m'
fi

ask SSH_ADDRESS "SSH address (user@host[:port], e.g. user@example.com:2222, the port defaults to 22, when left out)" "" "ssh"
ask REMOTE_ROOT "Remote project root (folder holding the composer.json, e.g. /var/www/example.com or ~/example.com)" "" "remote-root"
ask REMOTE_WEB "Web directory (relative to the project root)" "web" "web-dir"
ask ORIGIN "Remote site URL, used for stage_file_proxy (e.g. https://www.example.com, leave empty to skip stage_file_proxy)" "" "origin" optional

# Validate and normalise the values.
# Split the SSH address ("user@host[:port]") into the ssh destination and
# the port (default: 22). A bare host (no "user@") is accepted as well, ssh
# then uses the User from ~/.ssh/config or the local username. It is not
# advertised, as the local username rarely matches the remote one:
SSH_TARGET="$SSH_ADDRESS"
SSH_PORT=22
case "$SSH_TARGET" in
*:*:*)
  die "Invalid SSH address '$SSH_ADDRESS'. IPv6 addresses are not supported, use a host name instead."
  ;;
*:*)
  SSH_PORT="${SSH_TARGET##*:}"
  SSH_TARGET="${SSH_TARGET%:*}"
  if [[ ! "$SSH_PORT" =~ ^[0-9]{1,5}$ ]] || [ "$SSH_PORT" -lt 1 ] || [ "$SSH_PORT" -gt 65535 ]; then
    die "Invalid SSH port '$SSH_PORT' in '$SSH_ADDRESS'."
  fi
  ;;
esac
case "$SSH_TARGET" in
"" | @* | *@ | *[[:space:]]* | -*) die "Invalid SSH address '$SSH_ADDRESS'. Use user@host[:port], e.g. user@example.com:2222." ;;
esac
# Shown in messages:
SSH_DISPLAY="${SSH_TARGET}:${SSH_PORT}"
[ "$REMOTE_ROOT" != "/" ] && REMOTE_ROOT="${REMOTE_ROOT%/}"
[ -n "$REMOTE_ROOT" ] || die "Invalid remote project root."
# Shown in messages as entered, e.g. "~/example.com":
REMOTE_ROOT_DISPLAY="$REMOTE_ROOT"
# All remote paths are quoted, so the remote shell never expands "~". Relative
# paths are relative to the remote home for ssh and rsync, so "~/path" is the
# same as "path" and "~" is the same as ".":
case "$REMOTE_ROOT" in
\~) REMOTE_ROOT="." ;;
\~/*) REMOTE_ROOT="${REMOTE_ROOT#\~/}" ;;
\~*) die "Invalid remote project root '$REMOTE_ROOT'. '~user' paths are not supported, use an absolute path instead." ;;
esac
REMOTE_WEB="${REMOTE_WEB#./}"
REMOTE_WEB="${REMOTE_WEB%/}"
if [ -z "$REMOTE_WEB" ] || [[ "$REMOTE_WEB" == /* ]] || [[ "/$REMOTE_WEB/" == */../* ]] || [[ ! "$REMOTE_WEB" =~ ^[A-Za-z0-9._/-]+$ ]]; then
  die "Invalid web directory '$REMOTE_WEB'. It must be relative to the project root, e.g. 'web'."
fi
# The origin is written into settings.php inside single quotes, so quotes and
# backslashes are not allowed. Empty skips the stage_file_proxy setup:
if [ -n "$ORIGIN" ]; then
  ORIGIN_PATTERN="^https?://[^[:space:]'\"\\\\]+\$"
  [[ "$ORIGIN" =~ $ORIGIN_PATTERN ]] || die "Invalid remote site URL '$ORIGIN'. It must start with http:// or https:// and must not contain spaces, quotes or backslashes."
  ORIGIN="${ORIGIN%/}"
fi

confirm_or_abort $'\n\e[33mThis command will NOT initialize a production ready copy of your Website! It will create a modified environment, focused on development and debugging.\n\nDO NOT PUSH IT BACK TO PRODUCTION!\n\nContinue anyway (y/n)?\e[0m'

# Ask for the personal settings (git identity, DRUPALCODE_TOKEN), written to
# .ddev/.env.web before the containers start:
if [ "$NON_INTERACTIVE" = 1 ]; then
  echo "Non-interactive mode, skipping personal settings. Set them in .ddev/.env.web later (see .ddev/.example.env.web)."
else
  bash .ddev/drowl-init/ask-personal-settings.sh
fi

# The local docroot: the remote one, except with --cleanup-composer, where our
# default composer.json (installer-paths) always uses "web":
if [ "$CLEANUP_COMPOSER" = 1 ]; then
  LOCAL_WEB="web"
else
  LOCAL_WEB="$REMOTE_WEB"
fi

R_ROOT="$(rq "$REMOTE_ROOT")"
R_WEB="$(rq "$REMOTE_WEB")"

# SSH with connection multiplexing, so the key passphrase is asked once and
# rsync reuses the connection. Short socket path (unix socket path limit):
TMP_DIR="$(mktemp -d /tmp/drowl-ssh.XXXXXX)"
SSH_OPTS=(-p "$SSH_PORT" -o ControlMaster=auto -o "ControlPath=$TMP_DIR/cm-%C" -o ControlPersist=10m -o ServerAliveInterval=30)
if [ "$NON_INTERACTIVE" = 1 ]; then
  SSH_OPTS+=(-o BatchMode=yes)
fi
# rsync takes the ssh command as a single string (-e), using the same options
# reuses the multiplexed connection:
RSYNC_RSH="ssh ${SSH_OPTS[*]}"
# Extra rsync options, set once the remote login PATH is known (see below):
RSYNC_OPTS=()
# Overall progress for the larger transfers (custom code), on a terminal and
# with rsync >= 3.1 only (the rsync / openrsync shipped with macOS lacks
# --info):
RSYNC_PROGRESS=()
if [ -t 1 ] && rsync --info=progress2 --version >/dev/null 2>&1; then
  RSYNC_PROGRESS=(--info=progress2)
fi

# The PATH of a remote login shell. "ssh host cmd" runs cmd in a non-login,
# non-interactive shell, which does not read the profile files. So on e.g.
# Plesk, where PHP lives in /opt/plesk/php/X.Y/bin and is only added to the
# PATH in the profile, "php" (and so "vendor/bin/drush") is not found. Set after
# connecting, every remote command then runs with it:
REMOTE_PATH=""

# Runs a command (a single string, run by the remote shell) on the remote:
rssh() {
  if [ -n "$REMOTE_PATH" ]; then
    ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "PATH=$(rq "$REMOTE_PATH"); export PATH; $*"
  else
    ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "$*"
  fi
}

printf '\n\e[32mAlright! Initialising your project from %s:%s. This will take a while...\e[0m\n' "$SSH_DISPLAY" "$REMOTE_ROOT_DISPLAY"

# ------------------------------------------------------------------------------
# 1. Connect
# ------------------------------------------------------------------------------

step "1/11 Connecting to ${SSH_DISPLAY}"

SSH_STARTED=1
if ! rssh true </dev/null; then
  die "Could not connect to '${SSH_DISPLAY}'. Check the address, the port and your SSH key (ssh-agent / ~/.ssh/config)."
fi
success "Connected."

# Read the PATH of a remote bash login shell, as a terminal session (e.g.
# PuTTY) gets it ("bash -l" reads /etc/profile, ~/.bash_profile / ~/.profile).
# Only the PATH is taken over, instead of running every command in a login
# shell, as profiles may print text (which would end up in the composer.json or
# the database dump). So the PATH is printed behind the marker
# "LOGIN_SHELL_PATH:" and only that line is read:
REMOTE_PATH="$(rssh "bash -lc $(rq 'printf "LOGIN_SHELL_PATH:%s\n" "$PATH"')" </dev/null 2>/dev/null | sed -n 's/^LOGIN_SHELL_PATH://p' | tail -n 1 || true)"
if [ -n "$REMOTE_PATH" ]; then
  info "Remote login PATH: ${REMOTE_PATH}"
  # rsync starts "rsync --server" on the remote through the same non-login
  # shell, so give it the PATH as well:
  RSYNC_OPTS=(--rsync-path="PATH=$(rq "$REMOTE_PATH"); export PATH; rsync")
else
  warn "Could not read the PATH of a remote login shell (bash -l), using the default PATH of non-interactive SSH commands."
fi

# Check the remote project in a single SSH call, before anything is changed
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
PROBE="$(rssh "sh -s -- $R_ROOT $R_WEB" <"$TMP_DIR/probe.sh")"

# Prints the value of the given key from the probe output, e.g. "probe php":
probe() {
  printf '%s\n' "$PROBE" | sed -n "s/^$1=//p" | tail -n 1
}

# Depending on the probe that failed, kill the script with the correct message:
[ "$(probe root)" = 1 ] || die "The remote project root '${REMOTE_ROOT_DISPLAY}' does not exist."
[ "$(probe composer_json)" = 1 ] || die "No composer.json found in '${REMOTE_ROOT_DISPLAY}'."
[ "$(probe web)" = 1 ] || die "The web directory '${REMOTE_WEB}' does not exist in '${REMOTE_ROOT_DISPLAY}'."
[ "$(probe settings)" = 1 ] || die "No '${REMOTE_WEB}/sites/default/settings.php' found in '${REMOTE_ROOT_DISPLAY}'."
[ "$(probe drush)" = 1 ] || die "'vendor/bin/drush' is missing on the remote. It is required to read the settings and dump the database."
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
# 2. Composer
# ------------------------------------------------------------------------------

step "2/11 Composer"
LOCAL_CHANGED=1

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

  ddev composer config --no-plugins allow-plugins.cweagans/composer-patches true
  ddev composer config --no-plugins allow-plugins.oomphinc/composer-installers-extender true
  ddev composer config --no-plugins allow-plugins.szeidler/composer-patches-cli true
  ddev composer config --no-plugins allow-plugins.tbachert/spi true
  ddev composer config --no-plugins allow-plugins.symfony/runtime true
  ddev composer config extra.composer-exit-on-patch-failure true
  ddev composer config extra.enable-patching true
  ddev composer config minimum-stability dev
  ddev exec --raw composer config repositories.asset-packagist --json '{"type": "composer","url": "https://asset-packagist.org"}'
  ddev exec --raw composer config extra.installer-types --json '["npm-asset", "bower-asset"]'
  ddev exec --raw composer config 'extra.installer-paths.web/libraries/{$name}' --json '["type:drupal-library", "type:npm-asset", "type:bower-asset"]'

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
# 3. Read the remote settings.php
# ------------------------------------------------------------------------------

step "3/11 Reading the remote settings.php"

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
# 4. Dev stack
# ------------------------------------------------------------------------------

step "4/11 Dev stack"

# Prints the given packages ("vendor/name[:constraint]"), which are neither in
# require nor in require-dev yet:
missing_packages() {
  local package
  for package in "$@"; do
    # "${package%%:*}" strips the version constraint ("drupal/coder:^8"):
    jq -e --arg n "${package%%:*}" '((.require // {}) | has($n)) or ((.["require-dev"] // {}) | has($n))' composer.json >/dev/null || printf '%s\n' "$package"
  done
}

# Requires the given packages, skipping the ones already present (no moving
# between require and require-dev).
# Usage: composer_require [--dev] [-W] package...
composer_require() {
  local args=() packages=() package
  while [ $# -gt 0 ] && [[ "$1" == -* ]]; do
    args+=("$1")
    shift
  done
  while IFS= read -r package; do
    packages+=("$package")
  done < <(missing_packages "$@")
  if [ ${#packages[@]} -eq 0 ]; then
    info "Already present: $*"
    return 0
  fi
  ddev composer require "${args[@]}" "${packages[@]}" --no-audit --no-interaction
}

# Allows the given composer plugins, unless they are already configured:
allow_plugins() {
  local plugin
  for plugin in "$@"; do
    jq -e --arg p "$plugin" '(.config["allow-plugins"] // {}) | if type == "object" then has($p) else true end' composer.json >/dev/null ||
      ddev composer config --no-plugins "allow-plugins.$plugin" true
  done
}

allow_plugins "${COMPOSER_ALLOWED_PLUGINS[@]}"

# Add general dependencies:
composer_require "${COMPOSER_PACKAGES[@]}"

# Add the development modules. They go to require-dev, as this composer.json
# belongs to a production site:
composer_require --dev "${COMPOSER_DEV_MODULES[@]}"

# Add DEV dependencies. "drupal/core-dev" (phpunit, phpstan, ...) must match
# the core version exactly, so prefer the remote core version, which keeps
# drupal/core where it is:
if [ -n "$(missing_packages drupal/core-dev)" ]; then
  if [ "$CLEANUP_COMPOSER" != 1 ] && [[ "$CORE_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    if ! ddev composer require --dev "drupal/core-dev:${CORE_VERSION}" -W --no-audit --no-interaction; then
      warn "Requiring drupal/core-dev:${CORE_VERSION} failed, used drupal/core-dev:^${CORE_MAJOR} instead, which may have updated drupal/core."
      ddev composer require --dev "drupal/core-dev:^${CORE_MAJOR}" -W --no-audit --no-interaction
    fi
  else
    ddev composer require --dev "drupal/core-dev:^${CORE_MAJOR}" -W --no-audit --no-interaction
  fi
fi
composer_require --dev "${COMPOSER_DEV_PACKAGES[@]}"

# PHP Codesniffer Setup:
ddev exec chmod +x vendor/bin/phpcs vendor/bin/phpcbf

# ------------------------------------------------------------------------------
# 5. Custom code
# ------------------------------------------------------------------------------

step "5/11 Custom code"

for dir in "${CUSTOM_CODE_DIRS[@]}"; do
  if rssh "test -d $R_ROOT/$R_WEB/$dir" </dev/null; then
    info "Fetching ${REMOTE_WEB}/${dir}..."
    mkdir -p "./$LOCAL_WEB/$dir"
    rsync -a "${RSYNC_OPTS[@]}" "${RSYNC_PROGRESS[@]}" "${RSYNC_EXCLUDES[@]}" -e "$RSYNC_RSH" "$SSH_TARGET:$REMOTE_ROOT/$REMOTE_WEB/$dir/" "./$LOCAL_WEB/$dir/"
  else
    info "No ${REMOTE_WEB}/${dir} on the remote, skipping."
  fi
done
# Like drowl-init, there are always custom module, theme and profile folders:
for dir in "${CUSTOM_CODE_DIRS[@]}"; do
  mkdir -p "./$LOCAL_WEB/$dir"
done

# ------------------------------------------------------------------------------
# 6. Database dump
# ------------------------------------------------------------------------------

step "6/11 Dumping the remote database (streamed, this can take a while)"

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
# 7. Start and import
# ------------------------------------------------------------------------------

step "7/11 Starting DDEV and importing the database"

# Require the "PHPMyAdmin" plugin:
echo 'Requiring the "ddev-phpmyadmin" plugin...'
ddev get ddev/ddev-phpmyadmin
# The containers are already running (for composer), restart them to load the
# add-on:
ddev restart

# Copy all initiation additions (VSCode settings, phpunit.xml, .mcp.json,
# AGENTS.md, CLAUDE.md, jsconfig.json, web/sites/default/settings*, ...). The
# web/ folder goes into the local docroot:
chmod u+w "./$LOCAL_WEB/sites/default" 2>/dev/null || true
# "*" and ".[!.]*" match all files incl. dotfiles (.vscode, .claude, ...), but
# not "." and "..":
for item in .ddev/initiation-additions/* .ddev/initiation-additions/.[!.]*; do
  [ -e "$item" ] || continue
  if [ "$(basename "$item")" = "web" ]; then
    mkdir -p "./$LOCAL_WEB"
    cp -R "$item/." "./$LOCAL_WEB/"
  else
    cp -R "$item" .
  fi
done
# Some of the copied files contain "web/" paths, point them to the docroot
# (only paths like "./web/", "/var/www/html/web/", "\"web/", "`web/", "(web/"):
if [ "$LOCAL_WEB" != "web" ]; then
  for file in "${DOCROOT_REWRITE_FILES[@]}"; do
    [ -f "$file" ] || continue
    sed -E "s#(/var/www/html/|\./|[\"\`(])web/#\1${LOCAL_WEB}/#g" "$file" >"$TMP_DIR/docroot-rewrite"
    cat "$TMP_DIR/docroot-rewrite" >"$file"
  done
  info "Rewrote the 'web/' paths in ${DOCROOT_REWRITE_FILES[*]} to '${LOCAL_WEB}/'."
fi

SETTINGS_PHP="./$LOCAL_WEB/sites/default/settings.php"
chmod 0755 "./$LOCAL_WEB/sites/default"
chmod 0644 "$SETTINGS_PHP"

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
# 8. Directories
# ------------------------------------------------------------------------------

step "8/11 Directories"

mkdir -p ./files/private ./files/sync ./files/translations ./tmp
success "Created the directories files/private, files/sync, files/translations and tmp."

# ------------------------------------------------------------------------------
# 9. Post-setup
# ------------------------------------------------------------------------------

step "9/11 Post-setup"

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

# Activate Error Logging:
ddev drush config-set system.logging error_level verbose -y

# Disable css and js aggregation:
ddev drush config-set system.performance css.preprocess 0 -y
ddev drush config-set system.performance js.preprocess 0 -y

# Set up the linters like the drupal.org GitLab CI: install core's node modules
# (eslint, stylelint, prettier, cspell) and link core's configs as fallbacks:
echo 'Installing core node modules for the linters... (This might take a bit)'
if ! ddev exec /var/www/html/.ddev/drupal-ci/setup.sh; then
  warn "The drupal-ci setup failed. Run 'ddev drupal-ci-update' to retry it."
fi

# Create the "normal" db dump, used by "ddev drowl-reset-db":
(
  umask 077
  ddev export-db "$DDEV_PROJECT" >./data/sql/db-complete-dump.sql.gz
)
echo "Created full database dump under data/sql/db-complete-dump.sql.gz"

# ------------------------------------------------------------------------------
# 10. Template cleanup
# ------------------------------------------------------------------------------

step "10/11 Template cleanup"

# Remove README.md:
rm -f ./README.md

# Remove git files (keep .gitignore, it ignores personal settings, secrets and generated files):
rm -rf ./.git ./.gitattributes

# ------------------------------------------------------------------------------
# 11. Summary
# ------------------------------------------------------------------------------

CURRENT_STEP="11/11 Summary"

# Project information, useful commands and tips (shared with the other
# drowl-init commands):
SUMMARY_OPTS=(--title="drowl-init-from-ssh finished!" --web-dir="$LOCAL_WEB")
if [ "$SANITIZE" != 1 ]; then
  SUMMARY_OPTS+=(--command="ddev import-db --file=${DUMP_FILE}|Import the original (unsanitized) remote database dump again.")
fi
bash .ddev/drowl-init/print-summary.sh "${SUMMARY_OPTS[@]}"

# Section heading, like in print-summary.sh:
summary_heading() {
  local line
  line="$(printf '%*s' $((70 - ${#2})) '' | tr ' ' '-')"
  printf '\n\e[1;%sm-- %s %s\e[0m\n\n' "$1" "$2" "$line"
}

# After the tips (print-summary.sh): notes, to do, warnings.
if [ ${#NOTES[@]} -gt 0 ]; then
  summary_heading 36 "Notes"
  for note_text in "${NOTES[@]}"; do
    printf -- '  - %s\n' "$note_text"
  done
fi

summary_heading 33 "To do"
printf -- '  - Take a look at the composer.json!\n'
if [ "$CLEANUP_COMPOSER" = 1 ]; then
  printf -- '  - --cleanup-composer: check the merged keys (require, require-dev, patches, repositories; the remote allow-plugins were added) and the package versions that moved (compare with the remote composer.lock).\n'
fi
printf -- '  - Check that all patches applied (see the composer output above).\n'
printf -- '  - The local %s comes from our template, not from the remote. Only the table prefix, the public files path and the stage_file_proxy origin were taken over. Compare it with the remote settings.php and its includes, and copy over what the site needs locally (e.g. config_sync_directory, file_private_path, $config overrides, custom $settings, Redis, Solr, environment-specific includes).\n' "$SETTINGS_PHP"
printf -- '  - Run "ddev drush cex" if the config should be versioned in files/sync.\n'
if [ -n "$ORIGIN" ]; then
  printf -- '  - The stage_file_proxy origin (%s) is set at the end of %s. Change it there if the public files should come from another site.\n' "$ORIGIN" "$SETTINGS_PHP"
  printf -- '  - Private files were not copied (stage_file_proxy only serves public files).\n'
else
  printf -- '  - No remote site URL was given, so stage_file_proxy was not set up and no files were copied. Copy the public files manually, or enable stage_file_proxy and set its origin at the end of %s ($config['"'"'stage_file_proxy.settings'"'"']['"'"'origin'"'"']).\n' "$SETTINGS_PHP"
fi
if [ "$SANITIZE" = 1 ]; then
  printf -- '  - The database was sanitized (user emails and passwords, except for users with the administrator role). Other personal data (e.g. webform submissions, orders, profile fields) is still in it.\n'
fi
printf -- '  - The database holds production data: never use "ddev share" or bind_all_interfaces. When done, remove it with "ddev delete -Oy" and delete data/sql.\n'
printf -- '  - This is NOT a production ready copy. DO NOT PUSH IT BACK TO PRODUCTION!\n'

if [ ${#WARNINGS[@]} -gt 0 ]; then
  summary_heading 31 "Warnings"
  for warning in "${WARNINGS[@]}"; do
    printf -- '  - %s\n' "$warning"
  done
fi
