#!/usr/bin/env bash

# Asks for the personal settings (git identity, DRUPALCODE_TOKEN) and writes
# them into ".ddev/.env.web" (gitignored), so they are loaded on "ddev start".
# See ".ddev/.example.env.web" for all available variables.
#
# Called at the start of the "drowl-init*" host commands, from the project root.
# Existing values are offered as defaults; other variables in the file are kept.

set -e

ENV_FILE=".ddev/.env.web"

# Skip when not run interactively (e.g. CI):
if [ ! -t 0 ]; then
  echo "No interactive terminal, skipping personal settings. Set them in ${ENV_FILE} later (see .ddev/.example.env.web)."
  exit 0
fi

# Reads the current value of a variable from the env file (without quotes):
get_env_value() {
  [ -f "$ENV_FILE" ] || return 0
  sed -n "s/^$1=//p" "$ENV_FILE" | tail -n 1 | sed -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'$/\1/" -e 's/\\\(["$\\]\)/\1/g'
}

# Quotes a value for the env file:
quote_env_value() {
  local value="$1"
  value="${value//\\/\\\\}"
  value="${value//\"/\\\"}"
  value="${value//\$/\\\$}"
  printf '"%s"' "$value"
}

# Sets a variable in the env file, replacing an existing entry:
set_env_value() {
  local tmp
  tmp="$(mktemp)"
  if [ -f "$ENV_FILE" ]; then
    grep -v "^$1=" "$ENV_FILE" >"$tmp" || true
  fi
  printf '%s=%s\n' "$1" "$(quote_env_value "$2")" >>"$tmp"
  mv "$tmp" "$ENV_FILE"
}

# Defaults: existing values from the env file (ignoring the placeholders of
# .example.env.web), then the host git config:
default_name="$(get_env_value GIT_AUTHOR_NAME)"
[ "$default_name" = "Your Name" ] && default_name=""
default_name="${default_name:-$(git config --global user.name 2>/dev/null || true)}"
default_mail="$(get_env_value GIT_AUTHOR_EMAIL)"
[ "$default_mail" = "you@example.com" ] && default_mail=""
default_mail="${default_mail:-$(git config --global user.email 2>/dev/null || true)}"
existing_token="$(get_env_value DRUPALCODE_TOKEN)"
if [ "$existing_token" = "your-fine-grained-token" ]; then
  existing_token=""
fi

echo -e $'\e[36mPersonal settings (saved to .ddev/.env.web). Leave empty to skip.\e[0m'

read -r -p "Git name${default_name:+ [$default_name]}: " git_name
git_name="${git_name:-$default_name}"

read -r -p "Git mail${default_mail:+ [$default_mail]}: " git_mail
git_mail="${git_mail:-$default_mail}"

echo "DRUPALCODE_TOKEN: fine-grained personal access token from git.drupalcode.org, used by Claude Code (see README.md, \"Personal settings\")."
read -r -s -p "DRUPALCODE_TOKEN${existing_token:+ [keep existing]}: " drupalcode_token
echo
drupalcode_token="${drupalcode_token:-$existing_token}"

if [ -z "$git_name" ] && [ -z "$git_mail" ] && [ -z "$drupalcode_token" ]; then
  echo "No personal settings given, skipping. You can set them in ${ENV_FILE} later (see .ddev/.example.env.web)."
  exit 0
fi

if [ ! -f "$ENV_FILE" ]; then
  printf '%s\n' "# Personal environment variables for the ddev web container (gitignored)." "# See .ddev/.example.env.web for all available variables." >"$ENV_FILE"
fi

if [ -n "$git_name" ]; then
  set_env_value GIT_AUTHOR_NAME "$git_name"
  set_env_value GIT_COMMITTER_NAME "$git_name"
fi
if [ -n "$git_mail" ]; then
  set_env_value GIT_AUTHOR_EMAIL "$git_mail"
  set_env_value GIT_COMMITTER_EMAIL "$git_mail"
fi
if [ -n "$drupalcode_token" ]; then
  set_env_value DRUPALCODE_TOKEN "$drupalcode_token"
fi

# The file contains a secret, keep it private:
chmod 600 "$ENV_FILE"

echo -e $"\e[32mSaved personal settings to ${ENV_FILE}.\n\e[0m"
