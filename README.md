# O3-Shop

![O3-Shop logo](https://raw.githubusercontent.com/o3-shop/o3-documentation/refs/heads/main/source/assets/logo.png "O3-Shop")

## Shop core package

This package is part of the O3 Shop. For more information, consult the [documentation](https://docs.o3-shop.com)

- License: GNU General Public License 3 [https://www.gnu.org/licenses/gpl-3.0.de.html](https://www.gnu.org/licenses/gpl-3.0.de.html)
- Website: [https://www.O3-Shop.com](https://www.O3-Shop.com)

## Contribute

If you want to contribute — or just play around with O3 Shop — here's the how-to.

## Setting up an environment to work on the O3 core

You need an up-and-running Docker environment. Anything like [Docker Desktop ](https://www.docker.com/products/docker-desktop/) or [Colima](https://formulae.brew.sh/formula/colima) will work.

We mostly work with Colima, so this setup is the most tested one.

### How to set up the environment for working on O3 Shop

Follow these three simple steps:

- Clone this [repository](https://github.com/o3-shop/shop-ce)
- Run `./docker.sh start` and it should be up and running.

Aaand: You're ready to go. Just open http://localhost:8080.

And in case you missed the Docker log message:

- Shop URL: http://localhost:8080
- Admin URL: http://localhost:8080/admin/
- Admin login: admin@example.com
- Admin Password: admin123

### What else comes with this package

#### Mailpit

Once the setup is complete, all emails are sent to Mailpit. You reach it at http://localhost:8025.

#### Adminer

Adminer is included in the standard installation. Try http://localhost:8081.

### Working on the storefront theme

The first `./docker.sh start` clones the storefront themes directly into the served paths via `git`:

- `source/Application/views/wave/` ← https://github.com/o3-shop/wave-theme
- `source/Application/views/o3-theme/` ← https://github.com/o3-shop/o3-Theme

So those directories ARE the upstream working trees. `git pull`, `git status`, and commits work in place — no out-of-tree clone or symlink dance.

Apache serves theme assets through symlinks pointing back into each working tree:

- `source/out/wave` → `../Application/views/wave/out/wave`
- `source/out/o3-theme` → `../Application/views/o3-theme/out/o3-theme`

This means asset edits show up live without re-copying anything.

#### Pushing theme changes upstream

The clone uses HTTPS so anonymous read works without SSH keys. To push back, switch the remote once:

```sh
cd source/Application/views/wave
git remote set-url origin git@github.com:o3-shop/wave-theme.git
```

Same for `source/Application/views/o3-theme/` ↔ `git@github.com:o3-shop/o3-Theme.git`.

#### Migrating from the old detached snapshot

If you set up shop-ce before this change, your `source/Application/views/wave/` is a detached snapshot from the old `wget`+`unzip` bootstrap with no `.git/` subdirectory. The next `./docker.sh start` will detect this, abort with a loud warning, and tell you exactly what to do.

The one-liner is:

```sh
./docker.sh stop && rm -rf source/Application/views/wave source/out/wave && ./docker.sh start
```

The entrypoint deliberately refuses to delete those paths itself — they're gitignored, so any in-progress theme edits there are not version-controlled by anything else and would be silently destroyed. If you have uncommitted edits, copy them out of the directory first, then run the cleanup, then replay them onto the fresh working tree (where you can commit and push them).

#### Windows host caveat

The bootstrap symlinks `source/out/<theme>` into the cloned tree. Apache runs in the Linux container and follows the symlink fine on any host OS, so the storefront renders normally everywhere. On Windows hosts where shop-ce is checked out under `C:\...` (Windows-native FS via Docker Desktop's bridge), Windows-side tooling (IDE indexing, `git status` from PowerShell against the `out/` path) may show the symlink as broken — that's a host-side cosmetic issue only; the shop runs. WSL2-native FS (`\\wsl$\...`) and Mac/Linux hosts have no issue.

### Testing 

To run the tests, you have two choices. 
1. Just run `./docker.sh test` in your terminal.
<br>or 
2. Run `./run-tests.sh` inside the Docker container

This will run all tests in the shop core package.

#### Coverage HTML report
The code coverage report is located in `coverage/html/index.html`. You can open it in your browser to see the results.

#### Coverage PhpStorm report
To view your coverage report directly in PhpStorm:
1.	Open the Coverage tab in PhpStorm
2.	Select “Import a report collected in CI from disk”
3.	Choose `coverage/coverage.xml`
Your coverage report will now display within the IDE.
# Bugs and issues

If you experience any bugs or issues, please report them in the section **O3-Shop (all versions)** of [https://github.com/o3-shop/o3-shop/issues](https://github.com/o3-shop/o3-shop/issues).

Even better: Fix them on your own and open a pull request 🥳

# Disclaimer

We all work on this amazing product [pro bono](https://en.wikipedia.org/wiki/Pro_bono). There is no sophisticated "runs on every conceivable environment" thing.

What we use - and what works for us as developers working on O3 Shop:

- Mac OS
- [PhpStorm](https://www.jetbrains.com/de-de/phpstorm/)
- [Colima](https://formulae.brew.sh/formula/colima)
- GitHub -- of course

Which means: Other environments most likely will work as well. Maybe they don't.

We're happy if you want to join us to expand the developer's universe to more than what we use on a daily basis.

Just drop us a note — or even better: open a pull request. The latter did not work very well in the past. That will change! 
