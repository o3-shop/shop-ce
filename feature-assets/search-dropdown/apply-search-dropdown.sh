#!/bin/bash
# apply-search-dropdown.sh
# Copies search suggest dropdown frontend files into the active theme.
# Run once after composer install.

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Detect theme
THEME=""
if [ -d "$PROJECT_ROOT/source/Application/views/o3-theme" ]; then
    THEME="o3-theme"
elif [ -d "$PROJECT_ROOT/source/Application/views/wave" ]; then
    THEME="wave"
else
    echo "Error: No theme directory found (checked o3-theme and wave)"
    exit 1
fi

echo "Detected theme: $THEME"

# Source files from per-theme directory
SRC_DIR="$SCRIPT_DIR/$THEME"
SRC_TPL="$SRC_DIR/tpl/widget/header/search.tpl"
SRC_JS="$SRC_DIR/src/js/widget/oxsearchsuggest.js"
SRC_CSS="$SRC_DIR/src/css/search-suggest.css"

# Verify source files exist
for f in "$SRC_TPL" "$SRC_JS" "$SRC_CSS"; do
    if [ ! -f "$f" ]; then
        echo "Error: Source file not found: $f"
        exit 1
    fi
done

# Set destination paths
DEST_TPL_DIR="$PROJECT_ROOT/source/Application/views/$THEME/tpl/widget/header"
DEST_CSS_DIR="$PROJECT_ROOT/source/out/$THEME/src/css"

if [ "$THEME" = "o3-theme" ]; then
    DEST_JS_DIR="$PROJECT_ROOT/source/out/$THEME/src/js/widget"
else
    DEST_JS_DIR="$PROJECT_ROOT/source/out/$THEME/src/js/widgets"
fi

# Create directories if they don't exist
mkdir -p "$DEST_TPL_DIR" "$DEST_JS_DIR" "$DEST_CSS_DIR"

# Copy files
cp "$SRC_TPL" "$DEST_TPL_DIR/search.tpl"
echo "  Copied search.tpl          -> $DEST_TPL_DIR/search.tpl"

cp "$SRC_JS" "$DEST_JS_DIR/oxsearchsuggest.js"
echo "  Copied oxsearchsuggest.js  -> $DEST_JS_DIR/oxsearchsuggest.js"

cp "$SRC_CSS" "$DEST_CSS_DIR/search-suggest.css"
echo "  Copied search-suggest.css  -> $DEST_CSS_DIR/search-suggest.css"

echo ""
echo "Done. Clear cache if needed:"
echo "  rm -rf source/tmp/smarty/*"
