#!/usr/bin/env bash

## Description: Builds a local DDEV development copy of an existing Drupal site from its server over SSH (composer files, custom code, database). Files are served via stage_file_proxy.
## Usage: drowl-init-from-ssh [flags]
## Example: "ddev drowl-init-from-ssh", "ddev drowl-init-from-ssh --cleanup-composer", "ddev drowl-init-from-ssh --non-interactive --yes --ssh-target=user@example.com --remote-root=/var/www/example.com --origin=https://www.example.com"
## Flags: [{"Name":"cleanup-composer","Usage":"Start from our default composer.json and only take over the dependency keys from the remote (only use it if you know what you are doing)"},{"Name":"ssh-target","Type":"string","Usage":"SSH target, e.g. user@example.com"},{"Name":"ssh-port","Type":"string","Usage":"SSH port (default: 22)"},{"Name":"remote-root","Type":"string","Usage":"Remote project root holding the composer.json, e.g. /var/www/example.com"},{"Name":"web-dir","Type":"string","Usage":"Web directory, relative to the project root (default: web)"},{"Name":"origin","Type":"string","Usage":"stage_file_proxy origin, e.g. https://www.example.com"},{"Name":"yes","Shorthand":"y","Usage":"Answer all confirmations with yes"},{"Name":"non-interactive","Usage":"Never prompt: missing values fail, personal settings are skipped (implied without a terminal)"}]

# Builds a local DDEV development copy of an existing Drupal site straight from
# its server over SSH:
# - composer.json / composer.lock (plus the local files they reference, e.g.
#   patches) and the custom modules, themes and profiles,
# - the database (incl. table prefix), streamed over SSH, so no dump file ever
#   lands on the server,
# - the active config comes with the database, public files are served via
#   stage_file_proxy, private files are not copied.
# On top of that, the same dev stack as "drowl-init" is set up.
#
# This is a host command, so it uses the host's ssh / rsync and the user's SSH
# key (ssh-agent / ~/.ssh/config) directly.
#
# Note: Keep this script compatible with bash 3.2 (macOS).

set -eo pipefail

# ------------------------------------------------------------------------------
# Output helpers
# ------------------------------------------------------------------------------

WARNINGS=()

info() {
  printf '\e[36m%s\e[0m\n' "$*"
}

success() {
  printf '\e[32m%s\e[0m\n' "$*"
}

# Prints a warning and collects it for the to-do summary at the end:
warn() {
  printf '\e[33mWARNING: %s\e[0m\n' "$*" >&2
  WARNINGS+=("$*")
}

die() {
  printf '\n\e[31mERROR: %s\e[0m\n' "$*" >&2
  exit 1
}

CURRENT_STEP="Preparation"
step() {
  CURRENT_STEP="$1"
  printf '\n\e[1;32m==> %s\e[0m\n' "$1"
}

usage() {
  cat <<'USAGE'
Usage: ddev drowl-init-from-ssh [--cleanup-composer] [options]

Builds a local DDEV development copy of an existing Drupal site from its server
over SSH. All values not given as options are asked up front, then the command
runs unattended.

Options:
  --cleanup-composer       Start from our default composer.json and only take
                           over the dependency keys from the remote.
  --ssh-target=USER@HOST   SSH target.
  --ssh-port=PORT          SSH port (default: 22).
  --remote-root=PATH       Remote project root holding the composer.json
                           (absolute, or relative to the remote home).
  --web-dir=DIR            Web directory, relative to the project root
                           (default: web).
  --origin=URL             stage_file_proxy origin, e.g. https://www.example.com
  -y, --yes                Answer all confirmations with yes.
  --non-interactive        Never prompt (implied without a terminal). Missing
                           values fail, personal settings are skipped.
USAGE
}

# Quotes a value for a remote POSIX shell:
rq() {
  printf "'%s'" "$(printf '%s' "$1" | sed "s/'/'\\\\''/g")"
}

# ------------------------------------------------------------------------------
# Arguments
# ------------------------------------------------------------------------------

CLEANUP_COMPOSER=0
ASSUME_YES=0
NON_INTERACTIVE=0
SSH_TARGET=""
SSH_PORT=""
REMOTE_ROOT=""
REMOTE_WEB=""
ORIGIN=""

set_option() {
  case "$1" in
  --ssh-target) SSH_TARGET="$2" ;;
  --ssh-port) SSH_PORT="$2" ;;
  --remote-root) REMOTE_ROOT="$2" ;;
  --web-dir) REMOTE_WEB="$2" ;;
  --origin) ORIGIN="$2" ;;
  esac
}

while [ $# -gt 0 ]; do
  case "$1" in
  --cleanup-composer) CLEANUP_COMPOSER=1 ;;
  -y | --yes) ASSUME_YES=1 ;;
  --non-interactive) NON_INTERACTIVE=1 ;;
  -h | --help)
    usage
    exit 0
    ;;
  --ssh-target=* | --ssh-port=* | --remote-root=* | --web-dir=* | --origin=*)
    set_option "${1%%=*}" "${1#*=}"
    ;;
  --ssh-target | --ssh-port | --remote-root | --web-dir | --origin)
    [ $# -ge 2 ] || die "Missing value for $1."
    set_option "$1" "$2"
    shift
    ;;
  *)
    usage >&2
    die "Unknown argument '$1'."
    ;;
  esac
  shift
done

# Without a terminal, nobody can answer the prompts:
if [ ! -t 0 ]; then
  NON_INTERACTIVE=1
fi

# ------------------------------------------------------------------------------
# Cleanup trap
# ------------------------------------------------------------------------------

TMP_DIR=""
SSH_OPTS=()
SSH_STARTED=0
DUMP_PART=""
LOCAL_CHANGED=0

# Removes temp files and partial dumps, closes the SSH master connection and
# names the failed step:
cleanup() {
  local status="${1:-0}"
  trap - EXIT INT TERM
  set +e
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
trap 'cleanup $?' EXIT
trap 'cleanup 130' INT
trap 'cleanup 143' TERM

# ------------------------------------------------------------------------------
# Preconditions
# ------------------------------------------------------------------------------

for cmd in ssh rsync jq gzip ddev sed; do
  command -v "$cmd" >/dev/null 2>&1 || die "'$cmd' is required on the host, but was not found. Please install it first."
done

[ -d .ddev/initiation-additions ] || die "Run this command from the root of the ddev-vscode-devcontainer-drupal-template project."

# ------------------------------------------------------------------------------
# Prompts (all asked up front, afterwards the command runs unattended)
# ------------------------------------------------------------------------------

# Asks for a value, unless it was given as an option.
# Usage: ask VARIABLE "Prompt" [default] [option-name]
ask() {
  local var="$1" prompt="$2" default="$3" option="$4" value
  value="${!var}"
  if [ -z "$value" ]; then
    if [ "$NON_INTERACTIVE" = 1 ]; then
      [ -n "$default" ] || die "Missing value for --${option} (required in non-interactive mode)."
      value="$default"
    else
      while [ -z "$value" ]; do
        read -r -p $'\e[36m'"${prompt}${default:+ [$default]}: "$'\e[0m' value
        value="${value:-$default}"
      done
    fi
  fi
  printf -v "$var" '%s' "$value"
}

# Asks a y/n question, like drowl-init-from-existing. "n" aborts.
confirm_or_abort() {
  local question="$1" answer
  if [ "$ASSUME_YES" = 1 ]; then
    return 0
  fi
  if [ "$NON_INTERACTIVE" = 1 ]; then
    die "Confirmation required, pass --yes in non-interactive mode."
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

ask SSH_TARGET "SSH target (user@host)" "" "ssh-target"
ask SSH_PORT "SSH port" "22" "ssh-port"
ask REMOTE_ROOT "Remote project root (folder holding the composer.json, e.g. /var/www/example.com)" "" "remote-root"
ask REMOTE_WEB "Web directory (relative to the project root)" "web" "web-dir"
ask ORIGIN "stage_file_proxy origin (e.g. https://www.example.com)" "" "origin"

# Validate and normalise the values:
case "$SSH_TARGET" in
*[[:space:]]* | -*) die "Invalid SSH target '$SSH_TARGET'." ;;
esac
[[ "$SSH_PORT" =~ ^[0-9]+$ ]] || die "Invalid SSH port '$SSH_PORT'."
# "~/path" is the same as "path" for ssh (relative to the remote home):
REMOTE_ROOT="${REMOTE_ROOT#\~/}"
[ "$REMOTE_ROOT" != "/" ] && REMOTE_ROOT="${REMOTE_ROOT%/}"
[ -n "$REMOTE_ROOT" ] || die "Invalid remote project root."
REMOTE_WEB="${REMOTE_WEB#./}"
REMOTE_WEB="${REMOTE_WEB%/}"
if [ -z "$REMOTE_WEB" ] || [[ "$REMOTE_WEB" == /* ]] || [[ "/$REMOTE_WEB/" == */../* ]] || [[ ! "$REMOTE_WEB" =~ ^[A-Za-z0-9._/-]+$ ]]; then
  die "Invalid web directory '$REMOTE_WEB'. It must be relative to the project root, e.g. 'web'."
fi
[[ "$ORIGIN" =~ ^https?://[^[:space:]]+$ ]] || die "Invalid stage_file_proxy origin '$ORIGIN'. It must start with http:// or https://."
ORIGIN="${ORIGIN%/}"

confirm_or_abort $'\n\e[33mThis command will NOT initialize a production ready copy of your Website! It will create a modified environment, focused on development and debugging.\n\nDO NOT PUSH TO PRODUCTION!\n\nContinue anyway (y/n)?\e[0m'

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
RSYNC_RSH="ssh ${SSH_OPTS[*]}"

# Runs a command on the remote:
rssh() {
  ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "$@"
}

printf '\n\e[32mAlright! Initialising your project from %s:%s. This will take a while...\e[0m\n' "$SSH_TARGET" "$REMOTE_ROOT"

# ------------------------------------------------------------------------------
# 1. Connect
# ------------------------------------------------------------------------------

step "1/11 Connecting to ${SSH_TARGET}"

SSH_STARTED=1
if ! rssh true </dev/null; then
  die "Could not connect to '${SSH_TARGET}' (port ${SSH_PORT}). Check the target, the port and your SSH key (ssh-agent / ~/.ssh/config)."
fi
success "Connected."

# Check the remote project, in a single call:
cat >"$TMP_DIR/probe.sh" <<'SH'
cd "$1" 2>/dev/null || { echo "root=0"; exit 0; }
echo "root=1"
[ -f composer.json ] && echo "composer_json=1"
[ -f composer.lock ] && echo "composer_lock=1"
[ -x vendor/bin/drush ] && echo "drush=1"
[ -d "$2" ] && echo "web=1"
[ -f "$2/sites/default/settings.php" ] && echo "settings=1"
command -v php >/dev/null 2>&1 && echo "php=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
command -v rsync >/dev/null 2>&1 && echo "rsync=1"
command -v gzip >/dev/null 2>&1 && echo "gzip=1"
{ command -v mysqldump || command -v mariadb-dump; } >/dev/null 2>&1 && echo "mysqldump=1"
exit 0
SH
PROBE="$(rssh "sh -s -- $R_ROOT $R_WEB" <"$TMP_DIR/probe.sh")"

probe() {
  printf '%s\n' "$PROBE" | sed -n "s/^$1=//p" | tail -n 1
}

[ "$(probe root)" = 1 ] || die "The remote project root '${REMOTE_ROOT}' does not exist."
[ "$(probe composer_json)" = 1 ] || die "No composer.json found in '${REMOTE_ROOT}'."
[ "$(probe web)" = 1 ] || die "The web directory '${REMOTE_WEB}' does not exist in '${REMOTE_ROOT}'."
[ "$(probe settings)" = 1 ] || die "No '${REMOTE_WEB}/sites/default/settings.php' found in '${REMOTE_ROOT}'."
[ "$(probe drush)" = 1 ] || die "'vendor/bin/drush' is missing on the remote. It is required to dump the database."
[ -n "$(probe php)" ] || die "No PHP CLI found on the remote. It is required to read the settings.php."
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
REMOTE_DB_VERSION="$(rssh "cd $R_ROOT && vendor/bin/drush sql:query 'SELECT VERSION()'" </dev/null 2>/dev/null | tail -n 1 | tr -d '[:space:]' || true)"

# Use the same database type and version as the remote. The versions DDEV
# supports (see "database:" in .ddev/config.yaml, update on DDEV upgrades):
DDEV_MARIADB_VERSIONS="5.5 10.0 10.1 10.2 10.3 10.4 10.5 10.6 10.7 10.8 10.11 11.4 11.8 12.3"
DDEV_MYSQL_VERSIONS="5.5 5.6 5.7 8.0 8.4 9.7"

# Prints the given major.minor version, if DDEV supports it, otherwise the next
# higher supported one (a dump imports into a newer server), otherwise the
# highest supported one.
# Usage: pick_db_version VERSION SUPPORTED_VERSIONS
pick_db_version() {
  local want_major="${1%%.*}" want_minor="${1#*.}" candidate major minor last=""
  for candidate in $2; do
    major="${candidate%%.*}"
    minor="${candidate#*.}"
    last="$candidate"
    if [ "$major" -gt "$want_major" ] || { [ "$major" -eq "$want_major" ] && [ "$minor" -ge "$want_minor" ]; }; then
      printf '%s' "$candidate"
      return 0
    fi
  done
  printf '%s' "$last"
}

DB_FLAG=()
# e.g. "10.11.6-MariaDB-0+deb12u1", "5.5.5-10.11.6-MariaDB", "8.0.36-0ubuntu0.22.04.1":
REMOTE_DB_NUMBER="${REMOTE_DB_VERSION#5.5.5-}"
if [[ "$REMOTE_DB_NUMBER" =~ ^([0-9]+)\.([0-9]+) ]]; then
  REMOTE_DB_MAJOR_MINOR="${BASH_REMATCH[1]}.${BASH_REMATCH[2]}"
  case "$REMOTE_DB_VERSION" in
  *[Mm]aria[Dd][Bb]*)
    LOCAL_DB_TYPE="mariadb"
    LOCAL_DB_VERSION="$(pick_db_version "$REMOTE_DB_MAJOR_MINOR" "$DDEV_MARIADB_VERSIONS")"
    ;;
  *)
    LOCAL_DB_TYPE="mysql"
    LOCAL_DB_VERSION="$(pick_db_version "$REMOTE_DB_MAJOR_MINOR" "$DDEV_MYSQL_VERSIONS")"
    ;;
  esac
  if [ "$LOCAL_DB_VERSION" != "$REMOTE_DB_MAJOR_MINOR" ]; then
    warn "DDEV does not support ${LOCAL_DB_TYPE} ${REMOTE_DB_MAJOR_MINOR} (remote: ${REMOTE_DB_VERSION}), using ${LOCAL_DB_TYPE} ${LOCAL_DB_VERSION} locally."
  fi
  DB_FLAG=(--database="${LOCAL_DB_TYPE}:${LOCAL_DB_VERSION}")
else
  warn "Could not detect the remote database server version ('${REMOTE_DB_VERSION}'), using the database from .ddev/config.yaml."
fi
info "Remote: PHP ${REMOTE_PHP}, Drupal ${REMOTE_DRUPAL_VERSION:-unknown}, database server ${REMOTE_DB_VERSION:-unknown}."

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
  rssh "cd $R_ROOT && while IFS= read -r f; do [ -e \"\$f\" ] && printf '%s\n' \"\$f\"; done; exit 0" <"$list" >"$existing"
  while IFS= read -r p; do
    grep -Fxq -- "$p" "$existing" || warn "'$p' is referenced by the remote composer setup, but does not exist on the remote."
  done <"$list"
  [ -s "$existing" ] || return 0
  rsync -a -r --files-from="$existing" --exclude=node_modules --exclude=.git -e "$RSYNC_RSH" "$SSH_TARGET:$REMOTE_ROOT/" ./
}

# Lists the local files a composer.json / patches file references: patches,
# the patches-file, scaffold file-mapping sources and path repositories:
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
ddev config --project-type=drupal --docroot="$LOCAL_WEB" --webserver-type="apache-fpm" --composer-version="stable" "${PHP_FLAG[@]}" "${DB_FLAG[@]}" --disable-settings-management --auto
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

# The script is sent via stdin, so nothing is written on the remote. The
# database password is never printed:
cat >"$TMP_DIR/read-settings.php" <<'PHP'
<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 'stderr');
$drowl_project_root = getcwd();
$drowl_web_dir = $argv[1] ?? 'web';
$drowl_print = function (array $data) {
  while (ob_get_level() > 0) {
    ob_end_clean();
  }
  fwrite(STDOUT, 'DROWL_JSON:' . json_encode($data, JSON_UNESCAPED_SLASHES) . "\n");
};
$app_root = $drowl_project_root . '/' . $drowl_web_dir;
$site_path = 'sites/default';
$drowl_settings_file = $app_root . '/' . $site_path . '/settings.php';
if (!is_file($drowl_settings_file)) {
  $drowl_print(['error' => "No settings.php found at $drowl_settings_file."]);
  exit(0);
}
if (is_file($drowl_project_root . '/vendor/autoload.php')) {
  $class_loader = require $drowl_project_root . '/vendor/autoload.php';
}
$settings = [];
$databases = [];
$config = [];
$_SERVER += [
  'HTTP_HOST' => 'localhost',
  'SERVER_NAME' => 'localhost',
  'SERVER_PORT' => '80',
  'REQUEST_URI' => '/',
  'SCRIPT_NAME' => '/index.php',
  'REMOTE_ADDR' => '127.0.0.1',
];
chdir($app_root);
ob_start();
include $drowl_settings_file;
$drowl_db = $databases['default']['default'] ?? NULL;
if (!is_array($drowl_db)) {
  $drowl_print(['error' => "No \$databases['default']['default'] defined in $drowl_settings_file."]);
  exit(0);
}
$drowl_prefix = $drowl_db['prefix'] ?? '';
$drowl_prefix_array = is_array($drowl_prefix);
if ($drowl_prefix_array) {
  $drowl_prefix = $drowl_prefix['default'] ?? '';
}
// Multisite: a sites.php with entries, or further sites with a settings.php:
$drowl_multisite = FALSE;
if (is_file($app_root . '/sites/sites.php')) {
  $sites = [];
  include $app_root . '/sites/sites.php';
  $drowl_multisite = !empty($sites);
}
foreach (glob($app_root . '/sites/*/settings.php') ?: [] as $drowl_file) {
  if (!in_array(basename(dirname($drowl_file)), ['default', 'simpletest'], TRUE)) {
    $drowl_multisite = TRUE;
  }
}
$drowl_print([
  'error' => NULL,
  'driver' => (string) ($drowl_db['driver'] ?? ''),
  'host' => (string) ($drowl_db['host'] ?? ''),
  'port' => (string) ($drowl_db['port'] ?? ''),
  'database' => (string) ($drowl_db['database'] ?? ''),
  'username' => (string) ($drowl_db['username'] ?? ''),
  'prefix' => (string) $drowl_prefix,
  'prefix_array' => $drowl_prefix_array,
  'file_public_path' => (string) ($settings['file_public_path'] ?? 'sites/default/files'),
  'multisite' => $drowl_multisite,
]);
PHP
SETTINGS_OUTPUT="$(rssh "cd $R_ROOT && php -- $R_WEB" <"$TMP_DIR/read-settings.php")" || die "Reading the remote settings.php failed."

SETTINGS_JSON="$(printf '%s\n' "$SETTINGS_OUTPUT" | sed -n 's/^DROWL_JSON://p' | tail -n 1)"
[ -n "$SETTINGS_JSON" ] || die "Reading the remote settings.php failed (no result). Output was: ${SETTINGS_OUTPUT}"

setting() {
  printf '%s' "$SETTINGS_JSON" | jq -r ".$1 // empty"
}

SETTINGS_ERROR="$(setting error)"
[ -z "$SETTINGS_ERROR" ] || die "$SETTINGS_ERROR"

DB_DRIVER="$(setting driver)"
if [ "$DB_DRIVER" != "mysql" ]; then
  die "The remote database driver is '${DB_DRIVER:-unknown}'. Only 'mysql' (MySQL / MariaDB) is supported, as DDEV's database is MySQL / MariaDB."
fi

DB_PREFIX="$(setting prefix)"
if [ "$(setting prefix_array)" = "true" ]; then
  warn "The remote uses an array as table prefix (deprecated), using prefix['default'] ('${DB_PREFIX}')."
fi
[[ "$DB_PREFIX" =~ ^[A-Za-z0-9_]*$ ]] || die "Invalid remote table prefix '${DB_PREFIX}'."

FILE_PUBLIC_PATH="$(setting file_public_path)"

if [ "$(setting multisite)" = "true" ]; then
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

allow_plugins cweagans/composer-patches oomphinc/composer-installers-extender szeidler/composer-patches-cli tbachert/spi symfony/runtime \
  dealerdirect/phpcodesniffer-composer-installer phpstan/extension-installer php-http/discovery

# Add general dependencies:
composer_require cweagans/composer-patches szeidler/composer-patches-cli oomphinc/composer-installers-extender

# Add the development modules. They go to require-dev, as this composer.json
# belongs to a production site:
composer_require --dev drupal/devel drupal/stage_file_proxy

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
composer_require --dev drush/drush drupal/coder:^8 phpstan/phpstan-deprecation-rules kint-php/kint

# PHP Codesniffer Setup:
composer_require --dev squizlabs/php_codesniffer:^3
ddev exec chmod +x vendor/bin/phpcs vendor/bin/phpcbf

# ------------------------------------------------------------------------------
# 5. Custom code
# ------------------------------------------------------------------------------

step "5/11 Custom code"

for dir in modules/custom themes/custom profiles/custom; do
  if rssh "test -d $R_ROOT/$R_WEB/$dir" </dev/null; then
    info "Fetching ${REMOTE_WEB}/${dir}..."
    mkdir -p "./$LOCAL_WEB/$dir"
    rsync -a --exclude=node_modules --exclude=.git -e "$RSYNC_RSH" "$SSH_TARGET:$REMOTE_ROOT/$REMOTE_WEB/$dir/" "./$LOCAL_WEB/$dir/"
  else
    info "No ${REMOTE_WEB}/${dir} on the remote, skipping."
  fi
done
# Like drowl-init, there is always a custom module folder:
mkdir -p "./$LOCAL_WEB/modules/custom"

# ------------------------------------------------------------------------------
# 6. Database dump
# ------------------------------------------------------------------------------

step "6/11 Dumping the remote database (streamed, this can take a while)"

mkdir -p ./data/sql

# Only the structure of cache, sessions and watchdog tables. drush drops
# entries matching no table, so list them with and without the prefix:
STRUCTURE_TABLES="cache,cache_*,sessions,watchdog"
if [ -n "$DB_PREFIX" ]; then
  STRUCTURE_TABLES="${STRUCTURE_TABLES},${DB_PREFIX}cache,${DB_PREFIX}cache_*,${DB_PREFIX}sessions,${DB_PREFIX}watchdog"
fi

# The dump is piped straight into the SSH channel, so the server never holds a
# dump file that could be left behind. If the connection drops, the remote dump
# gets SIGPIPE / SIGHUP and exits:
DUMP_PART="data/sql/remote-dump.sql.gz.part"
DUMP_FILE="data/sql/remote-dump-$(date +%Y-%m-%d_%H-%M-%S).sql.gz"
REMOTE_DUMP_CMD="cd $R_ROOT && vendor/bin/drush sql:dump $(rq "--structure-tables-list=$STRUCTURE_TABLES") | gzip -c"
if ! rssh "bash -o pipefail -c $(rq "$REMOTE_DUMP_CMD")" </dev/null >"$DUMP_PART"; then
  die "The remote database dump failed."
fi

# Local integrity check, only then the .part file is renamed:
gzip -t "$DUMP_PART" || die "The database dump is corrupt (gzip -t failed)."
DUMP_TAIL="$(gzip -dc "$DUMP_PART" | tail -n 3)"
[[ "$DUMP_TAIL" == *"-- Dump completed"* ]] || die "The database dump is incomplete (no '-- Dump completed' trailer)."
mv "$DUMP_PART" "$DUMP_FILE"
DUMP_PART=""
success "Database dumped to ${DUMP_FILE} ($(du -h "$DUMP_FILE" | cut -f1))."

# ------------------------------------------------------------------------------
# 7. Start and import
# ------------------------------------------------------------------------------

step "7/11 Starting DDEV and importing the database"

ddev config --disable-settings-management --auto

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
  for file in .vscode/settings.json phpunit.xml jsconfig.json AGENTS.md; do
    [ -f "$file" ] || continue
    sed -E "s#(/var/www/html/|\./|[\"\`(])web/#\1${LOCAL_WEB}/#g" "$file" >"$TMP_DIR/docroot-rewrite"
    cat "$TMP_DIR/docroot-rewrite" >"$file"
  done
  info "Rewrote the 'web/' paths in .vscode/settings.json, phpunit.xml, jsconfig.json and AGENTS.md to '${LOCAL_WEB}/'."
fi

SETTINGS_PHP="./$LOCAL_WEB/sites/default/settings.php"
chmod 0755 "./$LOCAL_WEB/sites/default"
chmod 0644 "$SETTINGS_PHP"

# Replaces a line in the local settings.php (portable, no "sed -i"):
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

ddev import-db --file="$DUMP_FILE"

# ------------------------------------------------------------------------------
# 8. Directories
# ------------------------------------------------------------------------------

step "8/11 Directories"

mkdir -p ./files/private ./files/translations ./tmp

# ------------------------------------------------------------------------------
# 9. Post-setup
# ------------------------------------------------------------------------------

step "9/11 Post-setup"

if ! ddev drush updb -y; then
  warn "'drush updb' failed. Run 'ddev drush updb' again and check the output."
fi
ddev drush cr

# Activate the development modules:
ddev drush en devel stage_file_proxy -y

# Set the stage_file_proxy origin:
ddev drush config-set stage_file_proxy.settings origin "$ORIGIN" -y

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
ddev export-db "$DDEV_PROJECT" >./data/sql/db-complete-dump.sql.gz
echo "Created full database dump under data/sql/db-complete-dump.sql.gz"

# One-time login link (the imported users' passwords stay unchanged):
LOGIN_LINK="$(ddev drush uli 2>/dev/null | tail -n 1 || true)"

# ------------------------------------------------------------------------------
# 10. Template cleanup
# ------------------------------------------------------------------------------

step "10/11 Template cleanup"

# Remove README.md:
rm -f ./README.md

# Remove git files (keep .gitignore, it ignores personal settings, secrets and generated files):
rm -rf ./.git ./.gitattributes

# Give all Project informations:
ddev describe

# Notice about debugging inside attached VS-Code:
echo -e $'\e\n[33mNOTE: To debug inside the attached VS-Code instance, run `ddev config global --xdebug-ide-location=container && ddev restart`\n\e[0m'
echo -e $'\e\n[33mNOTE: To enable xhprof profiling, run `ddev config global --xhprof-mode=xhgui && ddev restart`\n\e[0m'

# Helper Messages
echo "Use 'ddev code' to attach VSCode to your running Container."
echo "Use 'ddev phpunit path/to/tests' to Test Classes using PHPUnit."
echo "Use 'ddev phpunit-coverage path/to/cover' to create a test coverage of the given file-directory."
echo "Use 'ddev phpcs ${LOCAL_WEB}/modules/custom/my_module' (or phpcbf, phpstan, eslint, stylelint, cspell) to check a project like the drupal.org GitLab CI does."
echo "Use 'ddev eslint --fix path' or 'ddev stylelint --fix path' to autofix JavaScript / CSS."
echo "Use 'ddev drupal-ci-update' to update the drupal.org GitLab CI default configs to the latest gitlab_templates 1.x release and refresh the linter setup."
echo "Use 'ddev xdebug on' to turn on xdebug, then in VSCode go to 'Run and Debug', 'Listen for XDebug' and open your Project in the Browser."
echo "Use 'ddev drowl-reset-db' to reset the database to its state after initial startup."
echo "Use 'ddev import-db --file=${DUMP_FILE}' to import the original remote database dump again."

printf "\nNote, that most of the linting services are also available in the attached VSCode Container as Extensions"
printf "\nFor more informations on the cli tools, visit https://github.com/webksde/ddev-vscode-devcontainer-drupal-template\n"

# Notice about the personal settings:
echo -e $'\e\n[33mNOTE: Personal settings (git identity, DRUPALCODE_TOKEN) are stored in .ddev/.env.web. Edit it (see .ddev/.example.env.web for all available variables), then run `ddev restart`.\n\e[0m'

# ------------------------------------------------------------------------------
# 11. To-do summary
# ------------------------------------------------------------------------------

step "11/11 Done! Manual follow-ups"

printf '\n\e[1;33mTo do:\e[0m\n'
printf -- '- Take a look at the composer.json: remove production-only packages (devel and stage_file_proxy were added to require-dev, the dev stack as in drowl-init).\n'
if [ "$CLEANUP_COMPOSER" = 1 ]; then
  printf -- '- --cleanup-composer: check the merged keys (require, require-dev, patches, repositories; the remote allow-plugins were added) and the package versions that moved (compare with the remote composer.lock).\n'
fi
printf -- '- Check that all patches applied (see the composer output above).\n'
printf -- '- Compare the remote settings includes (e.g. Redis, Solr, environment settings) with the local %s.\n' "$SETTINGS_PHP"
printf -- '- Run "ddev drush cex" if the config should be versioned in files/sync.\n'
printf -- '- Private files were not copied (stage_file_proxy only serves public files).\n'
printf -- '- This is NOT a production ready copy. DO NOT PUSH TO PRODUCTION!\n'

if [ ${#WARNINGS[@]} -gt 0 ]; then
  printf '\n\e[1;33mWarnings during the run:\e[0m\n'
  for warning in "${WARNINGS[@]}"; do
    printf -- '- %s\n' "$warning"
  done
fi

if [ -n "$LOGIN_LINK" ]; then
  printf '\n\e[32mLog in: %s\e[0m\n' "$LOGIN_LINK"
else
  printf '\n\e[33mRun "ddev drush uli" to get a login link.\e[0m\n'
fi
