<?php declare(strict_types=1);

namespace Dragonwize\PhpCodeQuality\Tests\PhpCsFixer\Rule;

use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\PhpDocTypeUnionNoSpaceFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpDocTypeUnionNoSpaceFixerTest extends TestCase
{
    private PhpDocTypeUnionNoSpaceFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new PhpDocTypeUnionNoSpaceFixer();
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
        self::assertSame('Dragonwize/php_doc_type_union_no_space', $this->fixer->getName());
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
        $tokens = Tokens::fromCode("<?php\n/**\n * @param null | string \$x\n */\nfunction foo(\$x) {}\n");

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
        yield 'spaces around pipe removed in @param' => [
            "<?php\n/**\n * @param null | string \$x\n */\nfunction foo(\$x) {}\n",
            "<?php\n/**\n * @param null|string \$x\n */\nfunction foo(\$x) {}\n",
        ];

        yield 'spaces around pipe removed in @return' => [
            "<?php\n/**\n * @return int | null\n */\nfunction foo() {}\n",
            "<?php\n/**\n * @return int|null\n */\nfunction foo() {}\n",
        ];

        yield 'spaces around pipe removed in @var' => [
            "<?php\n/** @var string | null \$x */\n\$x = null;\n",
            "<?php\n/** @var string|null \$x */\n\$x = null;\n",
        ];

        yield 'multiple unions all cleaned up' => [
            "<?php\n/**\n * @param string | int | null \$x\n */\nfunction foo(\$x) {}\n",
            "<?php\n/**\n * @param string|int|null \$x\n */\nfunction foo(\$x) {}\n",
        ];

        yield 'spaces around intersection operator removed' => [
            "<?php\n/**\n * @param Foo & Bar \$x\n */\nfunction foo(\$x) {}\n",
            "<?php\n/**\n * @param Foo&Bar \$x\n */\nfunction foo(\$x) {}\n",
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
        yield 'already correct – no spaces around pipe' => [
            "<?php\n/**\n * @param null|string \$x\n */\nfunction foo(\$x) {}\n",
        ];

        yield 'single type no union' => [
            "<?php\n/**\n * @param string \$x\n */\nfunction foo(\$x) {}\n",
        ];

        yield 'no doc comment' => [
            '<?php $x = 1;',
        ];
    }
}
