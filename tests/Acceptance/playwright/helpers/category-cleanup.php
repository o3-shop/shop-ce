<?php

/**
 * Tests-only helper: delete a category by OXID using OXID's
 * Category::delete() so the oxleft/oxright nested-set ranges in the
 * surrounding tree stay consistent. Plain `DELETE FROM oxcategories`
 * leaves ancestors with bloated OXRIGHT and breaks getSubCatList()
 * lookups for the rest of the test suite.
 *
 * Usage (inside the shop container):
 *   php tests/Acceptance/playwright/helpers/category-cleanup.php <oxid> [<oxid> ...]
 *
 * Exit code: 0 if every requested OXID was deleted (or absent — idempotent);
 * non-zero only on bootstrap failure.
 */
require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../source/bootstrap.php';

$ids = array_slice($argv, 1);
if (empty($ids)) {
    fwrite(STDERR, "Usage: category-cleanup.php <oxid> [...]\n");
    exit(2);
}

$db = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

foreach ($ids as $oxid) {
    $cat = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
    if (!$cat->load($oxid)) {
        echo "absent : $oxid\n";
        continue;
    }

    // OXID's SeoEncoderCategory::setRelatedToCategorySeoUrlsAsExpired()
    // runs a subquery that assumes oxseo holds at most one row per
    // (oxobjectid, oxtype). With multi-language demo data the test
    // category has two rows (one per lang) and the subquery throws
    // SQLSTATE[21000] Cardinality violation. We never need the SEO
    // entries for these throwaway test categories — drop them first
    // and the delete proceeds cleanly.
    $db->execute(
        'DELETE FROM oxseo WHERE oxobjectid = ? AND oxtype = ?',
        [$oxid, 'oxcategory']
    );

    $cat->delete($oxid);
    echo "deleted: $oxid\n";
}
