<?php declare(strict_types=1);

namespace Dragonwize\PhpCodeQuality\Tests\PhpCsFixer\Rule;

use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\PhpDocGenericsSpaceAfterCommaFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpDocGenericsSpaceAfterCommaFixerTest extends TestCase
{
    private PhpDocGenericsSpaceAfterCommaFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new PhpDocGenericsSpaceAfterCommaFixer();
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
        self::assertSame('Dragonwize/php_doc_generics_space_after_comma', $this->fixer->getName());
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
        $tokens = Tokens::fromCode("<?php /** @var array<int,string> */\n\$x;");

        self::assertTrue($this->fixer->isCandidate($tokens));
    }

    public function testIsCandidateReturnsFalseWithoutDocComment(): void
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
        yield 'no space after comma in generic type' => [
            "<?php /** @var array<int,string> \$x */\n\$x;",
            "<?php /** @var array<int, string> \$x */\n\$x;",
        ];

        yield 'extra space before comma is removed' => [
            "<?php /** @var array<int ,string> \$x */\n\$x;",
            "<?php /** @var array<int, string> \$x */\n\$x;",
        ];

        yield 'extra spaces around comma are normalised' => [
            "<?php /** @var array<int  ,  string> \$x */\n\$x;",
            "<?php /** @var array<int, string> \$x */\n\$x;",
        ];

        yield 'nested generics with missing space' => [
            "<?php /** @var array<int,array<string,bool>> \$x */\n\$x;",
            "<?php /** @var array<int, array<string, bool>> \$x */\n\$x;",
        ];

        yield '@param annotation with no space after comma' => [
            "<?php\n/**\n * @param array<string,int> \$map\n */\nfunction foo(\$map) {}\n",
            "<?php\n/**\n * @param array<string, int> \$map\n */\nfunction foo(\$map) {}\n",
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
        yield 'already correct single space after comma' => [
            "<?php /** @var array<int, string> \$x */\n\$x;",
        ];

        yield 'no generics in docblock' => [
            "<?php /** @var string \$x */\n\$x;",
        ];

        yield 'no doc comment at all' => [
            '<?php $x = 1;',
        ];
    }
}
