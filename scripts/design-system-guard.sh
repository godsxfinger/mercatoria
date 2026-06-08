#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASELINE_DIR="$ROOT_DIR/.design-system-baseline"

UPDATE_BASELINE=false
if [[ "${1:-}" == "--update-baseline" ]]; then
  UPDATE_BASELINE=true
fi

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

collect_inline_styles() {
  rg -n --no-heading 'style="' "$ROOT_DIR/resources/views" 2>/dev/null || true
}

collect_custom_hex() {
  rg -n --no-heading '#[0-9A-Fa-f]{3,8}\b' \
    "$ROOT_DIR/public/css/styles.css" \
    "$ROOT_DIR/resources/views" 2>/dev/null || true
}

collect_gradients() {
  rg -n --no-heading 'gradient\(' \
    "$ROOT_DIR/public/css/styles.css" \
    "$ROOT_DIR/resources/views" 2>/dev/null || true
}

collect_selectors() {
  rg -n --no-heading '^\s*\.[A-Za-z0-9_-]+' "$ROOT_DIR/public/css/styles.css" 2>/dev/null \
    | sed -E 's/^\s*\.([A-Za-z0-9_-]+).*/\1/' || true
}

normalize() {
  sed "s|$ROOT_DIR/||g" | sort -u
}

collect_inline_styles | normalize > "$TMP_DIR/inline_styles.txt"
collect_custom_hex | normalize > "$TMP_DIR/custom_hex.txt"
collect_gradients | normalize > "$TMP_DIR/gradients.txt"
collect_selectors | normalize > "$TMP_DIR/selectors.txt"

if $UPDATE_BASELINE; then
  mkdir -p "$BASELINE_DIR"
  cp "$TMP_DIR/inline_styles.txt" "$BASELINE_DIR/inline_styles.txt"
  cp "$TMP_DIR/custom_hex.txt" "$BASELINE_DIR/custom_hex.txt"
  cp "$TMP_DIR/gradients.txt" "$BASELINE_DIR/gradients.txt"
  cp "$TMP_DIR/selectors.txt" "$BASELINE_DIR/selectors.txt"
  echo "Design baseline updated in $BASELINE_DIR"
  exit 0
fi

missing_baseline=false
for file in inline_styles.txt custom_hex.txt gradients.txt selectors.txt; do
  if [[ ! -f "$BASELINE_DIR/$file" ]]; then
    echo "Missing baseline file: $BASELINE_DIR/$file"
    missing_baseline=true
  fi
done

if $missing_baseline; then
  echo "Run: bash scripts/design-system-guard.sh --update-baseline"
  exit 1
fi

has_failures=false

compare_fail() {
  local name="$1"
  local baseline="$2"
  local current="$3"
  local diff_file="$TMP_DIR/${name}_new.txt"
  comm -13 "$baseline" "$current" > "$diff_file" || true
  if [[ -s "$diff_file" ]]; then
    echo ""
    echo "FAIL: New ${name//_/ } detected:"
    cat "$diff_file"
    has_failures=true
  fi
}

compare_warn() {
  local name="$1"
  local baseline="$2"
  local current="$3"
  local diff_file="$TMP_DIR/${name}_new.txt"
  comm -13 "$baseline" "$current" > "$diff_file" || true
  if [[ -s "$diff_file" ]]; then
    echo ""
    echo "WARN: New ${name//_/ } detected:"
    cat "$diff_file"
  fi
}

compare_fail "inline_styles" "$BASELINE_DIR/inline_styles.txt" "$TMP_DIR/inline_styles.txt"
compare_fail "custom_hex" "$BASELINE_DIR/custom_hex.txt" "$TMP_DIR/custom_hex.txt"
compare_fail "gradients" "$BASELINE_DIR/gradients.txt" "$TMP_DIR/gradients.txt"
compare_warn "visual_variants" "$BASELINE_DIR/selectors.txt" "$TMP_DIR/selectors.txt"

if $has_failures; then
  echo ""
  echo "Design system guard failed."
  exit 1
fi

echo "Design system guard passed (no new token violations)."
