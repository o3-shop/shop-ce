---
name: wave-theme legacy CSS build recipe (node:14 container)
description: How to faithfully rebuild wave-theme's committed styles.css — node-sass needs Node <=14, Gruntfile out-path needs a symlink harness, and package.json's bootstrap pin (4.1.3) does NOT match the committed CSS (built from 4.3.1)
type: reference
---

## The problem

wave-theme (`/Users/nick/o3/wave-theme`, github.com/o3-shop/wave-theme) has a legacy grunt toolchain that cannot run on a modern host:

- `grunt-sass@2.0.0` → `node-sass@4.14.1`: no prebuilt binary for current Node ABIs; source build uses long-removed V8 APIs. **Requires Node ≤14.**
- No `package-lock.json` → `npm ci` impossible; modern npm hard-fails install on a peer conflict (`grunt-combine-mq` wants grunt 0.4, root pins 1.0.2). npm 6 (ships with Node 14) tolerates it.
- `phantomjs-prebuilt` (transitive, lint-only) aborts `npm install` on arm64 — but npm 6 leaves node_modules fully extracted anyway; the failure is ignorable for CSS builds.
- Gruntfile `project.out = './../../../out/'` assumes the shop-installed layout (`<shop>/source/Application/views/wave/` → `<shop>/source/out/`). From the standalone repo root it resolves OUTSIDE the repo (`/Users/out`, unwritable). Same quirk family as o3-theme's gulpfile.
- **Bootstrap drift:** package.json pins `bootstrap@4.1.3`, but the committed `out/wave/src/css/styles.css` was built from **Bootstrap 4.3.1**. Building with 4.1.3 silently swaps the entire Bootstrap layer (~18 KB delta). Install `bootstrap@4.3.1 --no-save` before building if you need a diff confined to your own changes.

## Working recipe (verified 2026-07-16, commit ebb9256 in wave-theme)

```bash
# 1. install deps (phantomjs failure at the end is expected + harmless)
docker run --rm -v /Users/nick/o3/wave-theme:/repo -w /repo node:14 bash -c \
  "npm install --no-audit --no-fund; npm rebuild node-sass; \
   npm install bootstrap@4.3.1 --no-save --ignore-scripts"

# 2. build CSS through a 3-deep symlink harness so ../../../out lands in the repo's own out/
docker run --rm -v /Users/nick/o3/wave-theme:/repo -w /repo node:14 bash -c \
  "mkdir -p /repo/.buildharness/a/b && cd /repo/.buildharness/a/b && \
   ln -sf /repo/Gruntfile.js /repo/package.json /repo/node_modules /repo/build . && \
   ./node_modules/.bin/grunt sass postcss combine_mq cssmin --no-color"

# 3. clean up
rm -rf /Users/nick/o3/wave-theme/.buildharness /Users/nick/o3/wave-theme/node_modules
```

Notes:
- `npm rebuild node-sass` compiles the binding from source on linux-arm64 (node:14 image has python2/gcc) — works fine, ~1 min.
- Run the CSS task subset, NOT the grunt default task — default ends in `watch` (blocks forever) and `copy`/`uglify` regenerate non-CSS artifacts (commit noise).
- Expected residual diff noise vs old committed CSS: one dropped `-webkit-backdrop-filter` prefix on Bootstrap's `.toast` (autoprefixer browserslist-data drift). Everything else should be your own SCSS changes.
- o3-theme has the same out-path quirk in its gulpfile but a modern toolchain (dart-sass via gulp) — only the symlink harness is needed there, no container.
