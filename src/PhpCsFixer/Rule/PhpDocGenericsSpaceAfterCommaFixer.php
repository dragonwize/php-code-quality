<?php declare(strict_types=1);

namespace Dragonwize\PhpCodeQuality\PhpCsFixer\Rule;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpDocGenericsSpaceAfterCommaFixer implements FixerInterface
{
    public function getName(): string
    {
        return 'Dragonwize/php_doc_generics_space_after_comma';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'PHPDoc types commas must not be preceded by a whitespace, and must be succeeded by a single whitespace or newline.',
            [new CodeSample("<?php /** @var array<int,string> */\n")],
            '',
        );
    }

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
            if (!$tokens[$index]->isGivenKind([\T_DOC_COMMENT])) {
                continue;
            }

            $docBlock = new DocBlock($tokens[$index]->getContent());

            foreach ($docBlock->getAnnotations() as $annotation) {
                if (!$annotation->supportTypes()) {
                    continue;
                }

                $types = $annotation->getTypes();
                if ($types === []) {
                    continue;
                }

                $types = array_map(fn (string $x): string => $this->fixType($x), $types);

                $annotation->setTypes($types);
            }

            $newContent = $docBlock->getContent();
            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([\T_DOC_COMMENT, $newContent]);
        }
    }

    private function fixType(string $type): string
    {
        return preg_replace('/,(?!\\R)\\s*/', ', ', preg_replace('/\\h*,/', ',', $type));
    }
}
