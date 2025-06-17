# O3-Shop

![O3-Shop logo](https://raw.githubusercontent.com/o3-shop/o3-documentation/refs/heads/main/source/assets/logo.png "O3-Shop")

## Shop core package

This package is part of the O3 shop. For more information, consult the [documentation](https://docs.o3-shop.com)

- License: GNU General Public License 3 [https://www.gnu.org/licenses/gpl-3.0.de.html](https://www.gnu.org/licenses/gpl-3.0.de.html)
- Website: [https://www.O3-Shop.com](https://www.O3-Shop.com)

## Contribute

If you want to contribute - or just play around with O3 shop. Here's the how to.

## Setting up an environment to work on the O3 core

An up and running docker environment. Anything like [Docker Desktop ](https://www.docker.com/products/docker-desktop/) or [Colima](https://formulae.brew.sh/formula/colima). 

We work mostly with Colima. Expect this to be tested best.

### How to set up the environment for working on O3 shop

Follow these three simple steps:

- Clone this [repository](https://github.com/o3-shop/shop-ce)
- Run `./docker.sh start` and it should go up and running 

Aaand: You're ready to go. Just open http://localhost:8080. 

And just if you missed the docker log message:

- Shop URL: http://localhost:8080
- Admin URL: http://localhost:8080/admin/
- Admin login: admin@example.com
- Admin Password: admin123

### What else comes with this packages

#### mailpit

If you finish the setup, all emails are being sent to mailpit. You reach it at http://localhost:8025.

### adminer

Also, an adminer comes with the standard installation. Try http://localhost:8081.

# Bugs and issues

If you experience any bugs or issues, please report them in the section **O3-Shop (all versions)** of [https://github.com/o3-shop/shop-ce/issues](https://github.com/o3-shop/shop-ce/issues).

Even better: Fix them on your on and open a pull request. 🥳

# Disclaimer

We all work on a [pro bono](https://en.wikipedia.org/wiki/Pro_bono) base on this amazing product. There is no sophisticated "development runs on every thinkable environment" thing.

What we use - and what works for us as developers working on O3 shop:

- Mac OS
- [PhpStorm](https://www.jetbrains.com/de-de/phpstorm/)
- [Colima](https://formulae.brew.sh/formula/colima)
- Github -- of course

Which means: Other environments most likely will work as well. Maybe they don't.

We're happy if you want to join us to expand the developer's universe to more than what we use on a daily base.

Just drop us a note or best: Open a pull request. The latter did not work very well in the past. That will change! 
