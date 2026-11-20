#!/usr/bin/env bash

## Description: Open VSCode attached to Web-Container of the current Project
## Usage: code
## Example: "ddev code"

# Get the webserver container name:
WEBSERVER_NAME=ddev-"$DDEV_SITENAME"-web

# Check if container is running
if ! docker ps --format "{{.Names}}" | grep -q "^$WEBSERVER_NAME$"; then
    echo "Container $WEBSERVER_NAME is not running. Starting DDEV..."
    ddev restart
fi

# Write a "named container configuration" for the Dev Containers extension, so
# VSCode installs the recommended extensions automatically when attaching.
# It is keyed by the container name, so it applies to this project only and
# survives DDEV updates and container recreation (which wipes the extensions
# installed inside the container). The file is regenerated on every run from
# .vscode/extensions.json, so edit the recommendations there.
write_attached_container_config() {
  local extensions_file=".vscode/extensions.json"
  if [[ ! -f "$extensions_file" ]]; then
    extensions_file=".ddev/initiation-additions/.vscode/extensions.json"
  fi
  [[ -f "$extensions_file" ]] || return 0

  # extensions.json is JSONC: strip all comments (string-aware, also multi-line
  # block comments), then take only the "publisher.name" ids inside the
  # "recommendations" array (never "unwantedRecommendations"):
  local extensions
  extensions=$(awk '
    { src = src $0 "\n" }
    END {
      out = ""; in_str = 0; n = length(src)
      for (i = 1; i <= n; i++) {
        c = substr(src, i, 1); c2 = substr(src, i, 2)
        if (in_str) {
          out = out c
          if (c == "\\") { out = out substr(src, i + 1, 1); i++ }
          else if (c == "\"") in_str = 0
        } else if (c2 == "//") {
          while (i <= n && substr(src, i, 1) != "\n") i++
          out = out "\n"
        } else if (c2 == "/*") {
          i += 2
          while (i <= n && substr(src, i, 2) != "*/") i++
          i++
        } else {
          if (c == "\"") in_str = 1
          out = out c
        }
      }
      if (match(out, /"recommendations"[[:space:]]*:[[:space:]]*\[[^]]*\]/))
        print substr(out, RSTART, RLENGTH)
    }' "$extensions_file" \
    | grep -oE '"[A-Za-z0-9][A-Za-z0-9-]*\.[A-Za-z0-9][A-Za-z0-9.-]*"' \
    | awk 'NR > 1 { printf ",\n    " } { printf "%s", $0 }')
  [[ -n "$extensions" ]] || return 0

  # VSCode user data directories (the Dev Containers extension runs on the
  # machine where the VSCode UI runs, which is Windows when using WSL):
  local user_dirs=()
  case "$(uname -s)" in
    Darwin) user_dirs+=("$HOME/Library/Application Support/Code/User" "$HOME/Library/Application Support/Code - Insiders/User") ;;
    *)      user_dirs+=("${XDG_CONFIG_HOME:-$HOME/.config}/Code/User" "${XDG_CONFIG_HOME:-$HOME/.config}/Code - Insiders/User") ;;
  esac
  if [[ -n "$APPDATA" ]]; then
    # Git Bash on Windows:
    user_dirs+=("$APPDATA/Code/User" "$APPDATA/Code - Insiders/User")
  elif grep -qi microsoft /proc/version 2>/dev/null && command -v wslpath >/dev/null; then
    local win_appdata
    win_appdata=$(cd /mnt/c 2>/dev/null && cmd.exe /c "echo %APPDATA%" 2>/dev/null | tr -d '\r')
    if [[ -n "$win_appdata" ]]; then
      win_appdata=$(wslpath "$win_appdata" 2>/dev/null)
      [[ -n "$win_appdata" ]] && user_dirs+=("$win_appdata/Code/User" "$win_appdata/Code - Insiders/User")
    fi
  fi

  local config
  config=$(printf '{\n  "workspaceFolder": "/var/www/html",\n  "extensions": [\n    %s\n  ]\n}\n' "$extensions")

  local user_dir config_dir written=0
  for user_dir in "${user_dirs[@]}"; do
    # Only for VSCode installations that exist:
    [[ -d "$user_dir" ]] || continue
    config_dir="$user_dir/globalStorage/ms-vscode-remote.remote-containers/nameConfigs"
    mkdir -p "$config_dir" && printf '%s' "$config" > "$config_dir/$WEBSERVER_NAME.json" && written=1
  done
  if [[ $written = 0 ]]; then
    echo "Note: No local VSCode user directory found, install the recommended extensions manually (Extensions view, search for '@recommended')."
  fi
}
write_attached_container_config

# Attach vscode to the webserver using a hex representation of the webserver name:
code --folder-uri="vscode-remote://attached-container+$(printf '%s' "$WEBSERVER_NAME" | od -A n -t x1 | sed 's/ *//g' | tr -d '\n')/var/www/html"
