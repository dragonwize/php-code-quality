<?php declare(strict_types=1);

namespace Dragonwize\PhpCodeQuality\Tests\PhpCsFixer\Rule;

use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\NoImportFromGlobalNamespaceFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NoImportFromGlobalNamespaceFixerTest extends TestCase
{
    private NoImportFromGlobalNamespaceFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new NoImportFromGlobalNamespaceFixer();
        Tokens::clearCache();
    }

    protected function tearDown(): void
    {
        Tokens::clearCache();
    }

    // -------------------------------------------------------------------------
    // Metadata tests
    // -------------------------------------------------------------------------

    public function testGetName(): void
    {
        self::assertSame('Dragonwize/no_import_from_global_namespace', $this->fixer->getName());
    }

    public function testGetPriority(): void
    {
        self::assertSame(0, $this->fixer->getPriority());
    }

    public function testIsNotRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
    }

    public function testSupportsAnyFile(): void
    {
        self::assertTrue($this->fixer->supports(new \SplFileInfo(__FILE__)));
    }

    public function testIsCandidateReturnsTrueWhenUsePresent(): void
    {
        $tokens = Tokens::fromCode('<?php use DateTime;');

        self::assertTrue($this->fixer->isCandidate($tokens));
    }

    public function testIsCandidateReturnsFalseWithoutUse(): void
    {
        $tokens = Tokens::fromCode('<?php $x = new \DateTime();');

        self::assertFalse($this->fixer->isCandidate($tokens));
    }

    // -------------------------------------------------------------------------
    // fix() – transformation cases
    // -------------------------------------------------------------------------

    #[DataProvider('provideFixCases')]
    public function testFix(string $input, string $expected): void
    {
        $tokens = Tokens::fromCode($input);
        $this->fixer->fix(new \SplFileInfo(__FILE__), $tokens);

        self::assertSame($expected, $tokens->generateCode());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideFixCases(): iterable
    {
        yield 'removes global namespace import and prefixes usage with backslash' => [
            '<?php
namespace Foo;
use DateTime;
class Bar {
    public function baz(DateTime $d): DateTime {}
}
',
            '<?php
namespace Foo;
class Bar {
    public function baz(\DateTime $d): \DateTime {}
}
',
        ];

        yield 'removes leading backslash global import and prefixes usage' => [
            '<?php
namespace Foo;
use \DateTime;
class Bar {
    public function baz(DateTime $d) {}
}
',
            '<?php
namespace Foo;
class Bar {
    public function baz(\DateTime $d) {}
}
',
        ];

        yield 'removes multiple global imports' => [
            '<?php
namespace Foo;
use DateTime;
use Exception;
class Bar {
    public function baz(DateTime $d, Exception $e) {}
}
',
            '<?php
namespace Foo;
class Bar {
    public function baz(\DateTime $d, \Exception $e) {}
}
',
        ];

        yield 'code in global namespace – import is removed but usages are not prefixed' => [
            '<?php
use DateTime;
$d = new DateTime();
',
            '<?php
$d = new DateTime();
',
        ];

        yield 'updates phpdoc type references' => [
            '<?php
namespace Foo;
use DateTime;
class Bar {
    /** @param DateTime $d */
    public function baz($d) {}
}
',
            '<?php
namespace Foo;
class Bar {
    /** @param \DateTime $d */
    public function baz($d) {}
}
',
        ];
    }

    // -------------------------------------------------------------------------
    // fix() – no-change cases
    // -------------------------------------------------------------------------

    #[DataProvider('provideNoChangeCases')]
    public function testFixDoesNotModify(string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $this->fixer->fix(new \SplFileInfo(__FILE__), $tokens);

        self::assertSame($code, $tokens->generateCode());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNoChangeCases(): iterable
    {
        yield 'import from non-global namespace is kept' => [
            '<?php
namespace Foo;
use Foo\Bar\Baz;
class Qux {
    public function quux(Baz $b) {}
}
',
        ];

        yield 'no use statements at all' => [
            '<?php
namespace Foo;
class Bar {
    public function baz(\DateTime $d) {}
}
',
        ];
    }
}
