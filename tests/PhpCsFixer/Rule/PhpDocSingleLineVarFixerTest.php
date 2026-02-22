<?php declare(strict_types=1);

namespace Dragonwize\PhpCodeQuality\Tests\PhpCsFixer\Rule;

use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\PhpDocSingleLineVarFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpDocSingleLineVarFixerTest extends TestCase
{
    private PhpDocSingleLineVarFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new PhpDocSingleLineVarFixer();
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
        self::assertSame('Dragonwize/php_doc_single_line_var', $this->fixer->getName());
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

    public function testIsCandidateReturnsTrueWhenDocCommentPresent(): void
    {
        $tokens = Tokens::fromCode('<?php /** @var string $x */ $x;');

        self::assertTrue($this->fixer->isCandidate($tokens));
    }

    public function testIsCandidateReturnsFalseWithoutDocComment(): void
    {
        $tokens = Tokens::fromCode('<?php $x = 1;');

        self::assertFalse($this->fixer->isCandidate($tokens));
    }

    // -------------------------------------------------------------------------
    // fix() – transformation cases (multi-line @var collapsed to single line)
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
        yield 'standard multi-line @var collapsed' => [
            '<?php
class Foo {
    /**
     * @var string
     */
    private $name;
}
',
            '<?php
class Foo {
    /** @var string */
    private $name;
}
',
        ];

        yield 'multi-line @var with variable name collapsed' => [
            '<?php
class Foo {
    /**
     * @var string $name
     */
    private $name;
}
',
            '<?php
class Foo {
    /** @var string $name */
    private $name;
}
',
        ];

        yield 'standalone multi-line @var outside class collapsed' => [
            "<?php\n/**\n * @var int\n */\n\$count = 0;\n",
            "<?php\n/** @var int */\n\$count = 0;\n",
        ];

        yield '@var with union type collapsed' => [
            "<?php\n/**\n * @var string|null\n */\n\$x = null;\n",
            "<?php\n/** @var string|null */\n\$x = null;\n",
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
        yield 'already single-line @var' => [
            '<?php /** @var string $x */ $x;',
        ];

        yield 'multi-line docblock with description and @var is not collapsed' => [
            '<?php
/**
 * Some description.
 *
 * @var string
 */
$x = "";
',
        ];

        yield 'multi-line docblock with multiple annotations is not collapsed' => [
            '<?php
/**
 * @var string
 * @see SomeClass
 */
$x = "";
',
        ];

        yield 'no doc comment' => [
            '<?php $x = 1;',
        ];
    }
}
