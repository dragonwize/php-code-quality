<?php

declare(strict_types=1);

namespace Dragonwize\PhpCodeQuality\Tests\PhpCsFixer\Rule;

use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\VariableNameCaseFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VariableNameCaseFixerTest extends TestCase
{
    private VariableNameCaseFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new VariableNameCaseFixer();
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
        self::assertSame('Dragonwize/variable_name_case', $this->fixer->getName());
    }

    public function testIsCandidateReturnsTrueWhenVariableTokenPresent(): void
    {
        $tokens = Tokens::fromCode('<?php $foo = 1;');

        self::assertTrue($this->fixer->isCandidate($tokens));
    }

    public function testIsCandidateReturnsFalseForCodeWithNoVariables(): void
    {
        $tokens = Tokens::fromCode('<?php echo "hello";');

        self::assertFalse($this->fixer->isCandidate($tokens));
    }

    // -------------------------------------------------------------------------
    // fix() – snake_case to camelCase conversion
    // -------------------------------------------------------------------------

    #[DataProvider('provideSnakeCaseToCamelCaseCases')]
    public function testFixConvertsSnakeCaseToCamelCase(string $input, string $expected): void
    {
        $tokens = Tokens::fromCode($input);
        $this->fixer->fix(new \SplFileInfo(__FILE__), $tokens);

        self::assertSame($expected, $tokens->generateCode());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideSnakeCaseToCamelCaseCases(): iterable
    {
        // The $ prefix acts as a non-delimiter boundary so the first letter after $
        // is NOT uppercased; only letters following an underscore are uppercased.
        // Result is lowerCamelCase (e.g. $my_variable → $myVariable).

        yield 'single underscore prefix word' => [
            '<?php $my_variable = 1;',
            '<?php $myVariable = 1;',
        ];

        yield 'multiple underscore segments' => [
            '<?php $first_second_third = true;',
            '<?php $firstSecondThird = true;',
        ];

        yield 'assignment and usage' => [
            '<?php $some_value = 10; echo $some_value;',
            '<?php $someValue = 10; echo $someValue;',
        ];

        yield 'multiple distinct variables' => [
            '<?php $foo_bar = 1; $baz_qux = 2;',
            '<?php $fooBar = 1; $bazQux = 2;',
        ];

        yield 'variable in function argument' => [
            '<?php function test($my_param) { return $my_param; }',
            '<?php function test($myParam) { return $myParam; }',
        ];

        yield 'variable in foreach' => [
            '<?php foreach ($items as $item_key => $item_value) {}',
            '<?php foreach ($items as $itemKey => $itemValue) {}',
        ];
    }

    // -------------------------------------------------------------------------
    // fix() – already-correct variables are left unchanged
    // -------------------------------------------------------------------------

    #[DataProvider('provideAlreadyCamelCaseCases')]
    public function testFixDoesNotModifyAlreadyCamelCaseVariables(string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $this->fixer->fix(new \SplFileInfo(__FILE__), $tokens);

        self::assertSame($code, $tokens->generateCode());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAlreadyCamelCaseCases(): iterable
    {
        yield 'simple camelCase' => ['<?php $myVariable = 1;'];
        yield 'single word lowercase' => ['<?php $foo = 1;'];
        yield 'single word uppercase first letter' => ['<?php $Foo = 1;'];
        yield 'long camelCase' => ['<?php $someVeryLongVariableName = true;'];
    }

    // -------------------------------------------------------------------------
    // fix() – $this is never renamed
    // -------------------------------------------------------------------------

    public function testFixDoesNotRenameThis(): void
    {
        $code = '<?php class Foo { public function bar() { return $this->baz; } }';
        $tokens = Tokens::fromCode($code);
        $this->fixer->fix(new \SplFileInfo(__FILE__), $tokens);

        self::assertSame($code, $tokens->generateCode());
    }

    // -------------------------------------------------------------------------
    // fix() – superglobals are never renamed
    // -------------------------------------------------------------------------

    #[DataProvider('provideSuperglobalCases')]
    public function testFixDoesNotRenameSuperglobals(string $code): void
    {
        $tokens = Tokens::fromCode($code);
        $this->fixer->fix(new \SplFileInfo(__FILE__), $tokens);

        self::assertSame($code, $tokens->generateCode());
    }

    /**
     * Variables that exist as keys in $GLOBALS are skipped by the fixer.
     * In CLI (PHPUnit) context, the available superglobals in $GLOBALS are:
     * _GET, _POST, _COOKIE, _FILES, _SERVER, argv, argc.
     *
     * @return iterable<string, array{string}>
     */
    public static function provideSuperglobalCases(): iterable
    {
        yield '$_GET' => ['<?php $value = $_GET["key"];'];
        yield '$_POST' => ['<?php $value = $_POST["key"];'];
        yield '$_SERVER' => ['<?php $host = $_SERVER["HTTP_HOST"];'];
        yield '$_COOKIE' => ['<?php $c = $_COOKIE["name"];'];
        yield '$_FILES' => ['<?php $f = $_FILES["upload"];'];
        yield '$GLOBALS' => ['<?php $GLOBALS["foo"] = 1;'];
    }

    // -------------------------------------------------------------------------
    // fix() – empty token stream does nothing
    // -------------------------------------------------------------------------

    public function testFixOnEmptyTokensDoesNothing(): void
    {
        // Tokens::fromCode with a non-PHP string produces a near-empty stream
        // We verify no exception is thrown and nothing breaks.
        $tokens = new Tokens();

        // Should not throw.
        $this->fixer->fix(new \SplFileInfo(__FILE__), $tokens);

        self::assertSame(0, $tokens->count());
    }

    // -------------------------------------------------------------------------
    // fix() – code without any variables is unchanged
    // -------------------------------------------------------------------------

    public function testFixDoesNotModifyCodeWithNoVariables(): void
    {
        $code = '<?php echo "hello world";';
        $tokens = Tokens::fromCode($code);
        $this->fixer->fix(new \SplFileInfo(__FILE__), $tokens);

        self::assertSame($code, $tokens->generateCode());
    }

    // -------------------------------------------------------------------------
    // fix() – mixed snake_case and camelCase in same file
    // -------------------------------------------------------------------------

    public function testFixHandlesMixedVariablesInSameFile(): void
    {
        $input = '<?php $already_camel = 1; $alreadyCamel = 2;';
        $tokens = Tokens::fromCode($input);
        $this->fixer->fix(new \SplFileInfo(__FILE__), $tokens);

        // $already_camel becomes $alreadyCamel (lowerCamelCase); the existing $alreadyCamel stays unchanged.
        self::assertSame('<?php $alreadyCamel = 1; $alreadyCamel = 2;', $tokens->generateCode());
    }
}
