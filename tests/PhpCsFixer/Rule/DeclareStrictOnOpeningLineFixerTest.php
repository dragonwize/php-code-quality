<?php declare(strict_types=1);

namespace Dragonwize\PhpCodeQuality\Tests\PhpCsFixer\Rule;

use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\DeclareStrictOnOpeningLineFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeclareStrictOnOpeningLineFixerTest extends TestCase
{
    private DeclareStrictOnOpeningLineFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new DeclareStrictOnOpeningLineFixer();
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
        self::assertSame('Dragonwize/declare_strict_on_opening_line', $this->fixer->getName());
    }

    public function testGetPriority(): void
    {
        self::assertSame(-31, $this->fixer->getPriority());
    }

    public function testIsNotRisky(): void
    {
        self::assertFalse($this->fixer->isRisky());
    }

    public function testSupportsAnyFile(): void
    {
        self::assertTrue($this->fixer->supports(new \SplFileInfo(__FILE__)));
    }

    public function testIsCandidateReturnsTrueWhenDeclarePresent(): void
    {
        $tokens = Tokens::fromCode('<?php declare(strict_types=1);');

        self::assertTrue($this->fixer->isCandidate($tokens));
    }

    public function testIsCandidateReturnsFalseWithoutDeclare(): void
    {
        $tokens = Tokens::fromCode('<?php $x = 1;');

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
        yield 'declare on separate line after open tag with newline' => [
            "<?php\ndeclare(strict_types=1);\n\$foo;\n",
            "<?php declare(strict_types=1);\n\$foo;\n",
        ];

        yield 'declare after open tag with code in between' => [
            "<?php\n\$foo;\ndeclare(strict_types=1);\n\$bar;\n",
            "<?php declare(strict_types=1);\n\$foo;\n\$bar;\n",
        ];

        yield 'declare already on opening line stays unchanged' => [
            '<?php declare(strict_types=1);',
            '<?php declare(strict_types=1);',
        ];

        yield 'declare with whitespace between open tag and declare' => [
            "<?php\n\ndeclare(strict_types=1);\n\$x = 1;\n",
            "<?php declare(strict_types=1);\n\$x = 1;\n",
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
        yield 'already on opening line' => ['<?php declare(strict_types=1);' . "\n"];

        yield 'declare without strict_types is ignored' => [
            "<?php\ndeclare(ticks=1);\n\$x = 1;\n",
        ];
    }

    // -------------------------------------------------------------------------
    // fix() – isCandidate() guards the fix path
    // -------------------------------------------------------------------------

    public function testIsCandidateReturnsFalseForCodeWithoutDeclare(): void
    {
        // A file with no declare statement at all is not a candidate,
        // so fix() would never be called on it by the runner.
        $tokens = Tokens::fromCode("<?php\n\$x = 1;\n");

        self::assertFalse($this->fixer->isCandidate($tokens));
    }
}
