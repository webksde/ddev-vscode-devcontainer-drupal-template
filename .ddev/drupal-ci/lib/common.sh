#!/bin/bash
# Variables are used by the runners that source this file.
# shellcheck disable=SC2034
#
# Shared helpers for the drupal-ci runners in ../bin. Source, don't execute.
#
# The runners reproduce the validate jobs of the drupal.org GitLab CI templates
# (https://git.drupalcode.org/project/gitlab_templates). A CI job always checks
# one "project" (a module, theme, profile or recipe repository), runs from the
# project's root and prefers the project's own config files over the defaults
# of the templates. The runners do the same: every path given is mapped to its
# project, and each project is checked separately.

DRUPAL_CI_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_ROOT="${DDEV_COMPOSER_ROOT:-/var/www/html}"
WEB_ROOT="$APP_ROOT/${DDEV_DOCROOT:-web}"
COMPOSER_BIN="$APP_ROOT/vendor/bin"
CORE_DIR="$WEB_ROOT/core"
NODE_BIN="$CORE_DIR/node_modules/.bin"

# Options passed through to the tool, and the projects to check with their
# targets (paths relative to the project root, newline separated).
CI_TOOL="$(basename "$0")"
CI_OPTS=()
CI_PROJECTS=()
declare -A CI_TARGETS=()

ci_die() {
  echo "drupal-ci: $*" >&2
  exit 2
}

ci_info() {
  echo "drupal-ci: $*" >&2
}

# Prints the root of the project a path belongs to: the topmost ancestor (below
# the app root) containing a *.info.yml or recipe.yml file. Prints nothing if
# the path is not part of a project.
ci_project_root() {
  local dir="$1" found=""
  [[ -d $dir ]] || dir="$(dirname "$dir")"
  while [[ $dir == "$APP_ROOT"/* ]]; do
    if compgen -G "$dir/*.info.yml" >/dev/null || [[ -f $dir/recipe.yml ]]; then
      found="$dir"
    fi
    dir="$(dirname "$dir")"
  done
  echo "$found"
}

# Prints the topmost projects found inside a directory.
ci_projects_below() {
  local dir="$1" candidate project
  local -a candidates=() projects=()
  mapfile -t candidates < <(
    find "$dir" \( -name node_modules -o -name vendor -o -name .git \) -prune -o \
      \( -name '*.info.yml' -o -name recipe.yml \) -type f -printf '%h\n' | sort -u
  )
  for candidate in "${candidates[@]}"; do
    for project in "${projects[@]}"; do
      [[ $candidate == "$project"/* ]] && continue 2
    done
    projects+=("$candidate")
  done
  [[ ${#projects[@]} -eq 0 ]] || printf '%s\n' "${projects[@]}"
}

ci_add_target() {
  local project="$1" target="$2"
  if [[ ! -v CI_TARGETS[$project] ]]; then
    CI_PROJECTS+=("$project")
    CI_TARGETS[$project]="$target"
  else
    CI_TARGETS[$project]+=$'\n'"$target"
  fi
}

# Splits the runner arguments into tool options (CI_OPTS) and paths, and maps
# the paths to projects. Options must use the "--name=value" form; everything
# after "--" is passed to the tool unchanged. Without paths, the current
# directory is used.
ci_parse_args() {
  local -a paths=()
  local arg abs project rel
  while [[ $# -gt 0 ]]; do
    arg="$1"
    shift
    if [[ $arg == "--" ]]; then
      CI_OPTS+=("$@")
      break
    elif [[ $arg == -* ]]; then
      CI_OPTS+=("$arg")
    else
      paths+=("$arg")
    fi
  done
  [[ ${#paths[@]} -gt 0 ]] || paths=("$PWD")

  for arg in "${paths[@]}"; do
    # -s: keep symlinked project folders inside the web root.
    abs="$(realpath -ms -- "$arg")"
    [[ -e $abs ]] || ci_die "Path not found: $arg"
    [[ $abs == "$APP_ROOT" || $abs == "$WEB_ROOT" ]] &&
      ci_die "Refusing to check the whole site ($abs). Pass a module, theme, profile or recipe path."
    project="$(ci_project_root "$abs")"
    if [[ -n $project ]]; then
      rel="$(realpath -ms --relative-to="$project" -- "$abs")"
      ci_add_target "$project" "$rel"
    elif [[ -d $abs ]]; then
      # A folder containing projects, e.g. web/modules/custom.
      local found=0
      while IFS= read -r project; do
        [[ -n $project ]] || continue
        found=1
        ci_add_target "$project" "."
      done < <(ci_projects_below "$abs")
      # No project at all: treat the folder itself as one.
      [[ $found == 1 ]] || ci_add_target "$abs" "."
    else
      ci_add_target "$(dirname "$abs")" "$(basename "$abs")"
    fi
  done
}

# Runs "$callback <project> <targets...>" from inside each project and returns
# the highest exit code.
ci_for_each_project() {
  local callback="$1" project rc=0 code
  local -a targets
  for project in "${CI_PROJECTS[@]}"; do
    mapfile -t targets <<<"${CI_TARGETS[$project]}"
    # Checking the whole project includes all other targets.
    local target
    for target in "${targets[@]}"; do
      [[ $target == "." ]] && targets=(".") && break
    done
    ci_info "$CI_TOOL ${project#"$APP_ROOT"/} (${targets[*]})"
    code=0
    (cd "$project" && "$callback" "$project" "${targets[@]}") || code=$?
    [[ $code -gt $rc ]] && rc=$code
  done
  return $rc
}

ci_is_tty() {
  [[ -t 1 ]]
}

# Installs the node tools exactly like CI: "yarn install" in core, using core's
# yarn.lock and the yarn version core pins via corepack.
ci_require_node_modules() {
  [[ -x $NODE_BIN/eslint ]] && return 0
  [[ -f $CORE_DIR/package.json ]] || ci_die "No $CORE_DIR/package.json found."
  ci_info "Installing core's node modules (yarn install in $CORE_DIR), this happens once..."
  (cd "$CORE_DIR" && COREPACK_ENABLE_DOWNLOAD_PROMPT=0 corepack yarn install >&2) ||
    ci_die "yarn install failed in $CORE_DIR."
}

# Like the CI eslint and stylelint jobs, provide core's configs to projects
# without their own. CI symlinks them into the project or its parent folder;
# here they always go into the parent folder (e.g. web/modules/custom), so the
# project itself stays untouched and the VSCode extensions find them as well.
ci_link_parent_configs() {
  local parent="$1"
  [[ $parent == "$WEB_ROOT"/* && $parent != "$CORE_DIR" && $parent != "$CORE_DIR"/* ]] || return 0
  [[ -w $parent ]] || return 0
  _ci_link "$CORE_DIR/.eslintrc.passing.json" "$parent/.eslintrc.json"
  _ci_link "$CORE_DIR/.eslintrc.jquery.json" "$parent/.eslintrc.jquery.json"
  _ci_link "$CORE_DIR/.prettierrc.json" "$parent/.prettierrc.json"
  # Stylelint resolves "extends" relative to a symlink, so it gets a real file.
  if [[ -f $CORE_DIR/.stylelintrc.json && ! -e $parent/.stylelintrc.json && ! -L $parent/.stylelintrc.json ]]; then
    printf '{\n  "extends": "%s"\n}\n' "$(realpath -ms --relative-to="$parent" "$CORE_DIR/.stylelintrc.json")" >"$parent/.stylelintrc.json"
  fi
  # CI writes this into projects without a .prettierignore.
  [[ -e $parent/.prettierignore ]] || echo '*.yml' >"$parent/.prettierignore"
}

_ci_link() {
  local target="$1" link="$2"
  [[ -e $target ]] || return 0
  [[ -e $link || -L $link ]] && return 0
  ln -sr "$target" "$link"
}

# Prints the PHPCS ruleset CI would use for a project: the project's own
# (.)phpcs.xml(.dist), else the default from gitlab_templates.
ci_phpcs_standard() {
  local project="$1" file
  for file in .phpcs.xml phpcs.xml .phpcs.xml.dist phpcs.xml.dist; do
    if [[ -f $project/$file ]]; then
      echo "$project/$file"
      return
    fi
  done
  echo "$DRUPAL_CI_DIR/assets/phpcs.xml.dist"
}
