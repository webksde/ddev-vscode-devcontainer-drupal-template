# shellcheck shell=bash
#
# Shared library of the "drowl-init*" host commands (.ddev/commands/host/).
# Sourced from the project root, not executed:
#   source .ddev/drowl-init/lib.sh
#
# - ui.sh: output (info, warn, step, ...), prompts, error handling,
# - steps.sh: setup steps shared by the commands, the final summary.
#
# Note: Keep all files compatible with bash 3.2 (macOS).

DROWL_LIB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=ui.sh
source "$DROWL_LIB_DIR/ui.sh"
# shellcheck source=steps.sh
source "$DROWL_LIB_DIR/steps.sh"
