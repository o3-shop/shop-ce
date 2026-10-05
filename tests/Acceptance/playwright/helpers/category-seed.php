<?php

/**
 * Tests-only helper: create a single category via OXID's
 * `Category::save()` so the oxleft/oxright nested-set ranges in the
 * surrounding tree stay consistent. Plain `INSERT INTO oxcategories`
 * leaves the parent's OXRIGHT untouched and breaks
 * `Category::getSubCatList()` for the rest of the test suite.
 *
 * Usage (inside the shop container):
 *   php tests/Acceptance/playwright/helpers/category-seed.php <title> <parent_oxid_or_'oxrootid'>
 *
 * Output: the assigned OXID on stdout (one line, no trailing whitespace).
 * Exit code: 0 on success, non-zero on bootstrap or save failure.
 */
require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../source/bootstrap.php';

if ($argc < 3) {
    fwrite(STDERR, "Usage: category-seed.php <title> <parent_oxid_or_'oxrootid'>\n");
    exit(2);
}

[$title, $parent] = [$argv[1], $argv[2]];

$cat = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
$cat->oxcategories__oxtitle = new \OxidEsales\Eshop\Core\Field(
    $title,
    \OxidEsales\Eshop\Core\Field::T_RAW,
);
$cat->oxcategories__oxparentid = new \OxidEsales\Eshop\Core\Field(
    $parent,
    \OxidEsales\Eshop\Core\Field::T_RAW,
);
$cat->oxcategories__oxactive = new \OxidEsales\Eshop\Core\Field(
    1,
    \OxidEsales\Eshop\Core\Field::T_RAW,
);
$cat->save();

$oxid = $cat->getId();
if (!$oxid) {
    fwrite(STDERR, "category-seed.php: save did not assign an OXID\n");
    exit(1);
}
echo $oxid;
