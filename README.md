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
