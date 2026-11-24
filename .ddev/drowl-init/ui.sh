# shellcheck shell=bash
#
# Output, prompt and error handling helpers shared by the "drowl-init*" host
# commands. Sourced via lib.sh, not executed.
#
# Note: Keep this file compatible with bash 3.2 (macOS).

# ------------------------------------------------------------------------------
# State
# ------------------------------------------------------------------------------

# The command name, e.g. "drowl-init", used in messages:
DROWL_NAME="$(basename "$0" .sh)"
DROWL_STEP=0
DROWL_STEP_TOTAL=0
DROWL_CURRENT_STEP="Preparation"
# Set to 1 once the first local file is changed, so a failure hints how to
# start over:
DROWL_LOCAL_CHANGED=0
# 1: never prompt (confirmations are skipped, missing values fail):
NON_INTERACTIVE="${NON_INTERACTIVE:-0}"
# Collected while running, printed in the summary at the end:
NOTES=()
TODOS=()
WARNINGS=()

# ------------------------------------------------------------------------------
# Colors (off without a terminal or with NO_COLOR, see https://no-color.org)
# ------------------------------------------------------------------------------

if [ -t 1 ] && [ -z "${NO_COLOR:-}" ]; then
  C_RESET=$'\e[0m'
  C_BOLD=$'\e[1m'
  C_DIM=$'\e[2m'
  C_RED=$'\e[31m'
  C_GREEN=$'\e[32m'
  C_YELLOW=$'\e[33m'
  C_CYAN=$'\e[36m'
else
  C_RESET="" C_BOLD="" C_DIM="" C_RED="" C_GREEN="" C_YELLOW="" C_CYAN=""
fi

# ------------------------------------------------------------------------------
# Messages
# ------------------------------------------------------------------------------

info() {
  printf '%s%s%s\n' "$C_CYAN" "$*" "$C_RESET"
}

success() {
  printf '%s%s%s\n' "$C_GREEN" "$*" "$C_RESET"
}

# Prints an info and collects it for the "Notes" section of the summary (for
# facts worth repeating at the end, e.g. versions):
note() {
  info "$@"
  NOTES+=("$*")
}

# Collects a follow-up task for the "To do" section of the summary:
todo() {
  TODOS+=("$*")
}

# Prints a warning and collects it for the "Warnings" section of the summary:
warn() {
  printf '%sWARNING: %s%s\n' "$C_YELLOW" "$*" "$C_RESET" >&2
  WARNINGS+=("$*")
}

die() {
  printf '\n%sERROR: %s%s\n' "$C_RED" "$*" "$C_RESET" >&2
  exit 1
}

# Starts the next step: "==> 3/9 Title". The total is the number of top-level
# "step" calls in the running command (counted in drowl_start), so it never
# has to be maintained by hand. Keep step calls at the start of a line.
step() {
  DROWL_STEP=$((DROWL_STEP + 1))
  if [ "$DROWL_STEP_TOTAL" -gt 0 ]; then
    DROWL_CURRENT_STEP="${DROWL_STEP}/${DROWL_STEP_TOTAL} $1"
  else
    DROWL_CURRENT_STEP="$1"
  fi
  printf '\n%s%s==> %s%s\n' "$C_BOLD" "$C_GREEN" "$DROWL_CURRENT_STEP" "$C_RESET"
}

# Section heading, e.g. "-- Useful commands -------". Usage: heading TEXT [COLOR]
heading() {
  local line
  line="$(printf '%*s' $((70 - ${#1})) '' | tr ' ' '-')"
  printf '\n%s%s-- %s %s%s\n\n' "$C_BOLD" "${2:-$C_CYAN}" "$1" "$line" "$C_RESET"
}

# A list item of a summary section:
bullet() {
  printf -- '  - %s\n' "$*"
}

banner() {
  local line
  line="$(printf '%*s' 74 '' | tr ' ' '=')"
  printf '\n%s%s%s\n  %s\n%s%s\n' "$C_BOLD" "${2:-$C_GREEN}" "$line" "$1" "$line" "$C_RESET"
}

# Prints the elapsed time since the command started, e.g. "4m 12s":
elapsed_time() {
  printf '%dm %02ds' $((SECONDS / 60)) $((SECONDS % 60))
}

# ------------------------------------------------------------------------------
# Start and error handling
# ------------------------------------------------------------------------------

# Prints the intro, counts the steps and installs the cleanup traps.
# A command can define "drowl_cleanup_hook" (called with the exit code) to
# remove its own temp files etc.
# Usage: drowl_start "Intro text"
drowl_start() {
  DROWL_STEP_TOTAL="$(grep -c '^step ' "$0" 2>/dev/null || true)"
  DROWL_STEP_TOTAL="${DROWL_STEP_TOTAL:-0}"
  # EXIT passes the script's exit code. On Ctrl-C (INT) / kill (TERM), $? is
  # not meaningful, so the usual shell codes are passed (128 + signal number):
  trap 'drowl_cleanup $?' EXIT
  trap 'drowl_cleanup 130' INT
  trap 'drowl_cleanup 143' TERM
  printf '\n%s%s%s\n' "$C_GREEN" "$1" "$C_RESET"
}

drowl_cleanup() {
  local status="${1:-0}"
  trap - EXIT INT TERM
  set +e
  stop_progress
  if declare -F drowl_cleanup_hook >/dev/null; then
    drowl_cleanup_hook "$status"
  fi
  if [ "$status" -ne 0 ]; then
    printf '\n%s%s failed at step "%s" (exit code %s).%s\n' "$C_RED" "$DROWL_NAME" "$DROWL_CURRENT_STEP" "$status" "$C_RESET" >&2
    if [ "$DROWL_LOCAL_CHANGED" = 1 ]; then
      printf '%sThe project is half initialised now. Start over from a fresh clone of the template (run "ddev delete -Oy" in this folder first).%s\n' "$C_RED" "$C_RESET" >&2
    fi
  fi
  exit "$status"
}

# ------------------------------------------------------------------------------
# Prompts
# ------------------------------------------------------------------------------

# Prints the prompt label in bold, followed by the dimmed hint and the
# highlighted default. The value is entered on the next line:
#   SSH address  user@host[:port], e.g. ...  [default: ...]
#   > _
# Usage: prompt_label "Label" ["Hint"] ["Default text"]
prompt_label() {
  printf '\n%s%s%s%s' "$C_BOLD" "$C_CYAN" "$1" "$C_RESET"
  [ -z "$2" ] || printf '  %s%s%s' "$C_DIM" "$2" "$C_RESET"
  [ -z "$3" ] || printf '  %s%s[%s]%s' "$C_BOLD" "$C_YELLOW" "$3" "$C_RESET"
  printf '\n'
}

# Reads a line into the given variable, with the "> " prompt. Pass -s to hide
# the input (secrets). Fails clearly if stdin is closed.
# Usage: prompt_read [-s] VARIABLE
prompt_read() {
  local silent=""
  if [ "$1" = "-s" ]; then
    silent=1
    shift
  fi
  if [ -n "$silent" ]; then
    read -r -s -p "${C_CYAN}> ${C_RESET}" "$1" || die "No input available. Use --non-interactive to run without prompts."
    printf '\n'
  else
    read -r -p "${C_CYAN}> ${C_RESET}" "$1" || die "No input available. Use --non-interactive to run without prompts."
  fi
}

# Asks for a value, unless it is already set (e.g. given as an option). With
# "optional", an empty value is accepted. A default that is not the value
# itself (e.g. the port) can be described with "default-note" instead, shown
# as is: "default port: 22".
# Usage: ask VARIABLE "Label" "Hint" [default] [option-name] [optional] [default-note]
ask() {
  local var="$1" label="$2" hint="$3" default="$4" option="$5" optional="$6" note="$7" value
  value="${!var}"
  if [ -z "$value" ]; then
    if [ "$NON_INTERACTIVE" = 1 ]; then
      [ -n "$default" ] || [ "$optional" = optional ] || die "Missing value for --${option} (required in non-interactive mode)."
      value="$default"
    else
      prompt_label "$label" "$hint" "${note:-${default:+default: $default}}"
      while true; do
        prompt_read value
        value="${value:-$default}"
        if [ -n "$value" ] || [ "$optional" = optional ]; then
          break
        fi
      done
    fi
  fi
  printf -v "$var" '%s' "$value"
}

# Rejects an answer. Interactive: prints the reason and clears the variable,
# so the prompt is asked again. Non-interactive: dies with the reason.
# Usage: reject VARIABLE "Reason"
reject() {
  [ "$NON_INTERACTIVE" = 1 ] && die "$2"
  printf '%s%s%s\n' "$C_RED" "$2" "$C_RESET" >&2
  printf -v "$1" '%s' ""
}

# Asks a y/n question, "n" aborts. Skipped in non-interactive mode.
# Usage: confirm_or_abort "Question?"
confirm_or_abort() {
  local answer
  [ "$NON_INTERACTIVE" = 1 ] && return 0
  while true; do
    read -r -p "${C_BOLD}${C_YELLOW}$1 [y/n] ${C_RESET}" answer || die "No input available. Use --non-interactive to run without prompts."
    case "$answer" in
    y* | Y*) return 0 ;;
    n* | N*)
      printf '%sAborting...%s\n' "$C_RED" "$C_RESET"
      exit 0
      ;;
    *) printf '%sPlease answer y or n.%s\n' "$C_YELLOW" "$C_RESET" ;;
    esac
  done
}

# ------------------------------------------------------------------------------
# Progress
# ------------------------------------------------------------------------------

# Progress line for a file that grows while it is downloaded: shows the size so
# far, the elapsed time and the average rate, redrawn every second on stderr.
# The final size is unknown (e.g. a streamed dump), so there is no percentage.
# Only on a terminal, so logs (CI, redirected output) stay clean. Stopped with
# stop_progress (and by drowl_cleanup on errors / Ctrl-C).
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
      awk -v label="$label" -v b="${bytes:-0}" -v e="$elapsed" -v c="$C_CYAN" -v r="$C_RESET" '
        function human(x,  u, i) {
          split("B KB MB GB TB", u, " ")
          for (i = 1; x >= 1024 && i < 5; i++) x /= 1024
          return sprintf(i == 1 ? "%d %s" : "%.1f %s", x, u[i])
        }
        BEGIN {
          printf "\r\033[K%s%s: %s (%02d:%02d, %s/s)%s", c, label, human(b), e / 60, e % 60, human(e > 0 ? b / e : 0), r
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
