#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

THEME="o3-theme"
SRC_DIR="$SCRIPT_DIR/$THEME"

DEST_TPL_DIR="$PROJECT_ROOT/source/Application/views/$THEME/tpl/widget/header"
DEST_JS_DIR="$PROJECT_ROOT/source/out/$THEME/src/js/widget"
DEST_CSS_DIR="$PROJECT_ROOT/source/out/$THEME/src/css"

mkdir -p "$DEST_TPL_DIR" "$DEST_JS_DIR" "$DEST_CSS_DIR"

cp "$SRC_DIR/tpl/widget/header/search.tpl" "$DEST_TPL_DIR/search.tpl"
echo "Copied search.tpl -> $DEST_TPL_DIR/search.tpl"

cp "$SRC_DIR/tpl/widget/header/searchsuggest.tpl" "$DEST_TPL_DIR/searchsuggest.tpl"
echo "Copied searchsuggest.tpl -> $DEST_TPL_DIR/searchsuggest.tpl"

cp "$SRC_DIR/src/js/widget/oxsearchsuggest.js" "$DEST_JS_DIR/oxsearchsuggest.js"
echo "Copied oxsearchsuggest.js -> $DEST_JS_DIR/oxsearchsuggest.js"

cp "$SRC_DIR/src/css/search-suggest.css" "$DEST_CSS_DIR/search-suggest.css"
echo "Copied search-suggest.css -> $DEST_CSS_DIR/search-suggest.css"

echo ""
echo "Done. Clear Smarty cache if needed:"
echo "  rm -rf source/tmp/smarty/*"
