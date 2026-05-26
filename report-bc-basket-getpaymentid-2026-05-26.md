# BC Notice — Basket::getPaymentId() as implicit public contract

**Date:** 2026-05-26  
**Analyst:** Nick Lorenz  
**Severity:** Low — production unaffected; downstream test stubs may break

---

## What changed

`OrderController::getPayment()` (line 279) calls `$oBasket->getPaymentId()` on whatever object `getBasket()` returns:

```php
$oBasket    = $this->getBasket();
$sPaymentid = $oBasket->getPaymentId();   // implicit basket contract
```

`Basket::getPaymentId()` is defined at `source/Application/Model/Basket.php:1888` and has existed in the codebase since the original O3-Shop fork. However, recent RC work on the 1.6.1 order path made this call the primary (and in some flows sole) way the payment ID is resolved inside `getPayment()`, making it a load-bearing part of the basket's public surface.

---

## Why this matters

This is **not a hard BC break** — the real `Basket` class has always implemented `getPaymentId()`. Shop operators and modules that use the basket normally are unaffected.

The break surface is **lightweight basket substitutes** used in module and downstream unit tests. Any test that stubs or mocks the basket with only a subset of methods (e.g. `getPriceForPayment()` only) and then exercises `OrderController::getPayment()` will fail with:

```
Error: Call to undefined method <StubClass>::getPaymentId()
```

The O3-Shop test suite already handles this correctly — see `tests/Unit/Application/Controller/OrderTest.php:744`, which mocks both `getPriceForPayment` and `getPaymentId` together. The risk is in downstream modules that copied an older, pre-RC5 mock pattern that only stubbed `getPriceForPayment`.

---

## Affected call site

| File | Line | Context |
|---|---|---|
| `source/Application/Controller/OrderController.php` | 279 | `$sPaymentid = $oBasket->getPaymentId();` |

---

## Impact assessment

| Area | Impact |
|---|---|
| Production runtime | None — `Basket::getPaymentId()` has always been implemented |
| O3-Shop own test suite | None — mocks already include `getPaymentId` |
| Downstream modules using real `Basket` | None |
| Downstream module tests stubbing basket without `getPaymentId` | **Breaks** — "Call to undefined method" |

---

## Recommended action

### Immediate (1.6.x)
- Document in release notes that `Basket::getPaymentId()` is now a required method for anything substituted as a basket in the `OrderController::getPayment()` call chain.

### 1.7.0
- Extract a formal `BasketInterface` (or expand the existing one if present) that includes `getPaymentId()`, so the contract is explicit and IDEs/static analysis tools can catch missing implementations before tests run.

---

## Fix for downstream module authors

Add `getPaymentId` to any basket stub used in `OrderController` tests:

```php
$oBasket = $this->getMock(
    \OxidEsales\Eshop\Application\Model\Basket::class,
    ['getPriceForPayment', 'getPaymentId']   // ← add getPaymentId
);
$oBasket->method('getPaymentId')->willReturn('oxidinvoice');
```
