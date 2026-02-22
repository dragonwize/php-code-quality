<?php declare(strict_types=1);

namespace Dragonwize\PhpCodeQuality\PhpCsFixer\Rule;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpDocSingleLineVarFixer implements FixerInterface
{
    public function getName(): string
    {
        return 'Dragonwize/php_doc_single_line_var';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'The `@var` annotation must be on a single line if it is the only content.',
            [
                new CodeSample('<?php
class Foo {
    /**
     * @var string
     */
    private $name;
}
'),
            ],
            '',
        );
    }

    /**
     * Must run after PhpdocLineSpanFixer.
     */
    public function getPriority(): int
    {
        return 0;
    }

    public function supports(\SplFileInfo $file): bool
    {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; --$index) {
            if (!$tokens[$index]->isGivenKind(\T_DOC_COMMENT)) {
                continue;
            }

            $newContent = preg_replace(
                '#^/\\*\\*[\\s\\*]+(@var[^\\r\\n]+)(?<!\\s)[\\s\\*]*\\*\\/$#',
                '/** $1 */',
                $tokens[$index]->getContent(),
            );

            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([\T_DOC_COMMENT, $newContent]);
        }
    }
}
