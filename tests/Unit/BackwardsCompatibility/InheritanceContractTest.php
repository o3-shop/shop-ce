<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * Inheritance-contract test for protected/public _method() names from the
 * pinned baseline revision. For each entry the test synthesises a subclass
 * via the unified namespace (OxidEsales\Eshop\...), overrides the underscore
 * method with an observable marker, invokes the non-underscore sibling with
 * type-defaulted arguments, and asserts the marker fired. Findings are
 * aggregated into openspec/changes/fix-underscore-method-inheritance/findings.json.
 *
 * The probe invokes arbitrary protected method bodies via reflection with
 * type-defaulted arguments. Some shop code paths reach Utils::redirect()
 * (and similar) which internally calls exit() — that would terminate the
 * PHP process with exit 0, silently aborting any PHPUnit run that includes
 * other tests. To keep the test safe inside the default suite, setUpBeforeClass
 * installs a Utils stub in the Registry whose redirect() and showMessageAndExit()
 * throw a RuntimeException instead of terminating PHP. Since every showMessageAndExit
 * and the sole redirect() live on Core\Utils (and the remaining `exit()` sites
 * under source/ are in code paths that call through Utils::redirect() first),
 * the stub eliminates the termination risk for every realistic probe.
 *
 * Design: openspec/changes/fix-underscore-method-inheritance/design.md (D3/D4/D7/D8)
 * Spec:   openspec/changes/fix-underscore-method-inheritance/specs/
 *         legacy-method-inheritance-contract/spec.md
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\BackwardsCompatibility;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

class InheritanceContractTest extends TestCase
{
    private const INVENTORY_PATH = __DIR__ . '/underscore-method-snapshot.json';
    private const FINDINGS_PATH = __DIR__ . '/../../../openspec/changes/fix-underscore-method-inheritance/findings.json';
    private const CONCRETE_PREFIX = 'OxidEsales\\EshopCommunity\\';
    private const UNIFIED_PREFIX = 'OxidEsales\\Eshop\\';

    /** @var array<int, array<string, mixed>> */
    private static array $findings = [];

    /** @var object|null */
    private static $originalUtils = null;

    public static function setUpBeforeClass(): void
    {
        self::$findings = [];
        self::installExitSafeUtilsStub();
    }

    /**
     * Replace the Registry-bound Utils with a subclass whose exit-calling
     * methods throw instead of terminating PHP. Prior tests in the same
     * PHPUnit run may have populated Registry with a live Utils — when a
     * probe body reaches Registry::getUtils()->redirect(...), the real
     * redirect() calls showMessageAndExit() which calls exit(). That kills
     * the whole PHPUnit process (exit 0, wrapper misreports success).
     * This stub converts both into catchable RuntimeExceptions so the
     * probe records the finding as exception_before_dispatch instead of
     * aborting the run.
     */
    private static function installExitSafeUtilsStub(): void
    {
        self::$originalUtils = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Core\Utils::class);

        $stub = new class () extends \OxidEsales\Eshop\Core\Utils {
            public function redirect($sUrl, $blAddRedirectParam = true, $iHeaderCode = 302): void
            {
                throw new \RuntimeException('InheritanceContractProbe: Utils::redirect intercepted to prevent exit()');
            }

            public function showMessageAndExit($sMsg): void
            {
                throw new \RuntimeException('InheritanceContractProbe: Utils::showMessageAndExit intercepted to prevent exit()');
            }
        };

        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Utils::class, $stub);
    }

    private static function uninstallExitSafeUtilsStub(): void
    {
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Utils::class, self::$originalUtils);
        self::$originalUtils = null;
    }

    public static function tearDownAfterClass(): void
    {
        self::uninstallExitSafeUtilsStub();

        usort(self::$findings, static fn (array $a, array $b): int => [$a['class'], $a['method']] <=> [$b['class'], $b['method']]);

        $dir = dirname(self::FINDINGS_PATH);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents(
            self::FINDINGS_PATH,
            json_encode(self::$findings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public function inventoryProvider(): iterable
    {
        $json = @file_get_contents(self::INVENTORY_PATH);
        if ($json === false) {
            self::fail('baseline inventory file not found: ' . self::INVENTORY_PATH);
        }
        /** @var array<int, array<string, mixed>> $entries */
        $entries = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        foreach ($entries as $entry) {
            yield $entry['class'] . '::' . $entry['method'] => [$entry];
        }
    }

    /**
     * @dataProvider inventoryProvider
     *
     * @param array<string, mixed> $entry
     */
    public function testUnderscoreShimPreservesOverride(array $entry): void
    {
        $concreteClass = (string) $entry['class'];
        $underscoreMethod = (string) $entry['method'];

        if (!str_starts_with($concreteClass, self::CONCRETE_PREFIX)) {
            self::fail("unexpected class prefix in inventory: {$concreteClass}");
        }

        $unifiedClass = self::UNIFIED_PREFIX . substr($concreteClass, strlen(self::CONCRETE_PREFIX));

        try {
            $classExists = class_exists($unifiedClass);
        } catch (Throwable $t) {
            self::markTestSkipped("autoload of unified class threw: {$unifiedClass} (" . $t->getMessage() . ')');
        }

        if (!$classExists) {
            self::markTestSkipped("unified class not loadable: {$unifiedClass}");
        }

        try {
            $reflection = new ReflectionClass($unifiedClass);
        } catch (Throwable $t) {
            self::markTestSkipped("reflection failed for {$unifiedClass}: " . $t->getMessage());
        }

        if (!$reflection->hasMethod($underscoreMethod)) {
            self::markTestSkipped("method removed from current tree: {$concreteClass}::{$underscoreMethod}()");
        }

        $siblingName = ltrim($underscoreMethod, '_');
        if ($siblingName === '' || !$reflection->hasMethod($siblingName)) {
            // Shape unchanged from baseline — no shim pair exists. Contract holds trivially.
            self::assertTrue(true);
            return;
        }

        // Only a *same-class* pair is a shim. If the non-underscore sibling is
        // inherited from a parent, it is a different method (same name, different
        // concept) — e.g. FrontendController::getVendorId() vs
        // VendorListController::_getVendorId(): the latter is an internal helper,
        // the former is the inherited public accessor. Those are not BC shim pairs
        // and must not be flagged or remediated.
        $siblingDeclaringClass = $reflection->getMethod($siblingName)->getDeclaringClass()->getName();
        $underscoreDeclaringClass = $reflection->getMethod($underscoreMethod)->getDeclaringClass()->getName();
        if ($siblingDeclaringClass !== $underscoreDeclaringClass) {
            // Not a same-class shim pair. Contract does not apply here.
            self::assertTrue(true);
            return;
        }

        // Exact allow-list for a single class/method pair that is not a shim
        // despite matching the prefix-strip heuristic:
        //   UtilsServer::_isCurrentUrl($sURL, $sServerHost) is a 2-arg internal
        //   helper; isCurrentUrl($sURL) is a 1-arg public entry point that
        //   calls it. Different signatures, different purposes — not a BC shim.
        // If another case appears later, add it here explicitly (do not
        // generalise this into a signature-mismatch rule).
        if ($unifiedClass === 'OxidEsales\\Eshop\\Core\\UtilsServer' && $underscoreMethod === '_isCurrentUrl') {
            self::assertTrue(true);
            return;
        }

        $this->checkDispatch($reflection, $underscoreMethod, $siblingName, $entry);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function checkDispatch(
        ReflectionClass $parentReflection,
        string $underscoreMethod,
        string $siblingMethod,
        array $entry
    ): void {
        $unifiedClass = $parentReflection->getName();

        // Skip cases that can't be probed cleanly.
        $parentMethod = $parentReflection->getMethod($underscoreMethod);
        if ($parentMethod->isAbstract()) {
            self::markTestSkipped("parent method is abstract: {$unifiedClass}::{$underscoreMethod}()");
        }

        $siblingMethod_r = $parentReflection->getMethod($siblingMethod);
        if ($siblingMethod_r->isAbstract()) {
            self::markTestSkipped("sibling method is abstract: {$unifiedClass}::{$siblingMethod}()");
        }

        try {
            $synthFqcn = $this->synthesizeProbeSubclass($unifiedClass, $parentMethod);
        } catch (Throwable $t) {
            self::markTestSkipped("could not synthesise probe for {$unifiedClass}::{$underscoreMethod}: " . $t->getMessage());
        }

        try {
            $instance = (new ReflectionClass($synthFqcn))->newInstanceWithoutConstructor();
        } catch (Throwable $t) {
            self::markTestSkipped("cannot instantiate probe for {$unifiedClass}: " . $t->getMessage());
        }

        $args = $this->defaultArgsFor($siblingMethod_r);

        $throwable = null;
        try {
            $bound = $siblingMethod_r->getClosure($instance);
            if ($bound !== null) {
                $bound(...$args);
            } else {
                $siblingMethod_r->setAccessible(true);
                $siblingMethod_r->invokeArgs($instance, $args);
            }
        } catch (Throwable $t) {
            $throwable = $t;
        }

        if ($instance->__inheritanceContractProbeFired ?? false) {
            self::assertTrue(true);
            return;
        }

        $observed = $throwable !== null ? 'exception_before_dispatch' : 'override_not_called';
        $notes = $throwable !== null
            ? get_class($throwable) . ': ' . $throwable->getMessage()
            : "{$siblingMethod}() did not dispatch to \$this->{$underscoreMethod}()";

        self::$findings[] = [
            'class' => (string) $entry['class'],
            'unified_class' => $unifiedClass,
            'method' => $underscoreMethod,
            'sibling_method' => $siblingMethod,
            'current_file' => $this->resolveCurrentFile($parentMethod),
            'observed' => $observed,
            'notes' => $notes,
        ];

        // Do not fail here — testNoContractViolations() is the single aggregate gate.
        self::assertTrue(true, 'finding recorded');
    }

    public function testNoContractViolations(): void
    {
        if (self::$findings === []) {
            self::assertTrue(true, 'no inheritance-contract violations');
            return;
        }

        $preview = array_map(
            static fn (array $f): string => sprintf('  - %s::%s  (%s)', $f['class'], $f['method'], $f['observed']),
            array_slice(self::$findings, 0, 15)
        );
        $more = count(self::$findings) - count($preview);

        self::fail(
            sprintf(
                "%d inheritance-contract violation(s) recorded. See findings.json. First entries:\n%s%s",
                count(self::$findings),
                implode("\n", $preview),
                $more > 0 ? "\n  ... and {$more} more" : ''
            )
        );
    }

    private function synthesizeProbeSubclass(string $parentFqcn, ReflectionMethod $underscoreMethod): string
    {
        $shortName = 'InheritanceContractProbe_' . bin2hex(random_bytes(8));
        $fq = __NAMESPACE__ . '\\' . $shortName;

        $signature = $this->renderMethodSignature($underscoreMethod);
        $returnStmt = $this->renderReturnStatement($underscoreMethod);

        $namespace = __NAMESPACE__;
        $code = <<<PHP
namespace {$namespace};
class {$shortName} extends \\{$parentFqcn} {
    public bool \$__inheritanceContractProbeFired = false;
    {$signature} {
        \$this->__inheritanceContractProbeFired = true;
        {$returnStmt}
    }
}
PHP;

        eval($code);

        if (!class_exists($fq, false)) {
            throw new \RuntimeException("probe class {$fq} not registered after eval");
        }
        return $fq;
    }

    private function renderMethodSignature(ReflectionMethod $m): string
    {
        $visibility = $m->isPrivate() ? 'private' : ($m->isProtected() ? 'protected' : 'public');
        $static = $m->isStatic() ? ' static' : '';

        $params = [];
        foreach ($m->getParameters() as $p) {
            $params[] = $this->renderParameter($p);
        }

        $returnType = '';
        if ($m->hasReturnType()) {
            $returnType = ': ' . $this->renderType($m->getReturnType());
        }

        return sprintf(
            '%s%s function %s(%s)%s',
            $visibility,
            $static,
            $m->getName(),
            implode(', ', $params),
            $returnType
        );
    }

    private function renderParameter(ReflectionParameter $p): string
    {
        $out = '';
        if ($p->hasType()) {
            $out .= $this->renderType($p->getType()) . ' ';
        }
        if ($p->isPassedByReference()) {
            $out .= '&';
        }
        if ($p->isVariadic()) {
            $out .= '...';
        }
        $out .= '$' . $p->getName();

        if (!$p->isVariadic() && $p->isDefaultValueAvailable()) {
            try {
                if ($p->isDefaultValueConstant()) {
                    // Use the constant name verbatim so enum and class const defaults work.
                    $constName = $p->getDefaultValueConstantName();
                    if ($constName !== null && $constName !== '') {
                        $out .= ' = \\' . ltrim($constName, '\\');
                        return $out;
                    }
                }
                $out .= ' = ' . var_export($p->getDefaultValue(), true);
            } catch (Throwable) {
                $out .= ' = null';
            }
        } elseif (!$p->isVariadic() && $p->isOptional()) {
            $out .= ' = null';
        }

        return $out;
    }

    private function renderType(?ReflectionType $t): string
    {
        if ($t === null) {
            return '';
        }
        if ($t instanceof ReflectionUnionType) {
            return implode('|', array_map(fn (ReflectionType $x): string => $this->renderSingleType($x), $t->getTypes()));
        }
        if ($t instanceof ReflectionIntersectionType) {
            return implode('&', array_map(fn (ReflectionType $x): string => $this->renderSingleType($x), $t->getTypes()));
        }
        return $this->renderSingleType($t);
    }

    private function renderSingleType(ReflectionType $t): string
    {
        if (!$t instanceof ReflectionNamedType) {
            return (string) $t;
        }
        $name = $t->getName();
        $builtIn = [
            'int', 'string', 'bool', 'float', 'void', 'iterable', 'object', 'mixed',
            'never', 'null', 'false', 'true', 'array', 'callable', 'self', 'static', 'parent',
        ];
        $qualified = in_array($name, $builtIn, true) ? $name : '\\' . $name;
        $nullable = $t->allowsNull() && !in_array($name, ['mixed', 'null'], true) ? '?' : '';
        // Union types already carry their own null; do not prefix with ? there.
        return $nullable . $qualified;
    }

    private function renderReturnStatement(ReflectionMethod $m): string
    {
        if (!$m->hasReturnType()) {
            return 'return null;';
        }
        $t = $m->getReturnType();
        if (!$t instanceof ReflectionNamedType) {
            // union / intersection — pick null if allowed, otherwise return without a value and rely on skip
            return 'return null;';
        }
        $name = $t->getName();
        if ($name === 'void') {
            return 'return;';
        }
        if ($name === 'never') {
            return 'throw new \\RuntimeException("never");';
        }
        if ($t->allowsNull()) {
            return 'return null;';
        }
        return match ($name) {
            'int' => 'return 0;',
            'float' => 'return 0.0;',
            'bool' => 'return false;',
            'string' => 'return "";',
            'array', 'iterable' => 'return [];',
            'self', 'static' => 'return $this;',
            default => 'return null;',
        };
    }

    /**
     * @return array<int, mixed>
     */
    private function defaultArgsFor(ReflectionMethod $m): array
    {
        $args = [];
        foreach ($m->getParameters() as $p) {
            if ($p->isVariadic() || $p->isOptional()) {
                break;
            }
            $args[] = $this->defaultValueForType($p->getType(), $p->allowsNull());
        }
        return $args;
    }

    private function defaultValueForType(?ReflectionType $t, bool $allowsNull): mixed
    {
        if ($allowsNull || $t === null) {
            return null;
        }
        if ($t instanceof ReflectionUnionType || $t instanceof ReflectionIntersectionType) {
            return null;
        }
        if (!$t instanceof ReflectionNamedType) {
            return null;
        }
        return match ($t->getName()) {
            'int' => 0,
            'float' => 0.0,
            'bool' => false,
            'string' => '',
            'array', 'iterable' => [],
            default => null,
        };
    }

    private function resolveCurrentFile(ReflectionMethod $m): string
    {
        $file = $m->getFileName();
        if ($file === false) {
            return '';
        }
        $repoRoot = realpath(__DIR__ . '/../../../');
        if ($repoRoot !== false && str_starts_with($file, $repoRoot . '/')) {
            return substr($file, strlen($repoRoot) + 1);
        }
        return $file;
    }
}
