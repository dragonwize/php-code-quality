<?php declare(strict_types=1);

namespace Dragonwize\PhpCodeQuality\PhpCsFixer\Rule;

use Dragonwize\PhpCodeQuality\PhpCsFixer\TokenRemover;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoImportFromGlobalNamespaceFixer implements FixerInterface
{
    public function getName(): string
    {
        return 'Dragonwize/no_import_from_global_namespace';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Use FQNS instead of importing from global namespace.',
            [new CodeSample('<?php
namespace Foo;
use DateTime;
class Bar {
    public function __construct(DateTime $dateTime) {}
}
')],
            '',
        );
    }

    /**
     * Must run before PhpdocAlignFixer.
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
        return $tokens->isTokenKindFound(\T_USE);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach (array_reverse(new NamespacesAnalyzer()->getDeclarations($tokens)) as $namespace) {
            self::fixImports($tokens, $namespace->getScopeStartIndex(), $namespace->getScopeEndIndex(), $namespace->getFullName() === '');
        }
    }

    private static function fixImports(Tokens $tokens, int $startIndex, int $endIndex, bool $isInGlobalNamespace): void
    {
        $importedClassesIndices = self::getImportCandidateIndices($tokens, $startIndex, $endIndex);

        if (!$isInGlobalNamespace) {
            for ($index = $endIndex; $index > $startIndex; --$index) {
                if ($tokens[$index]->isGivenKind(\T_DOC_COMMENT)) {
                    $importedClassesIndices = self::updateComment($tokens, $importedClassesIndices, $index);
                    continue;
                }

                if (!$tokens[$index]->isGivenKind(\T_STRING)) {
                    continue;
                }

                $importedClassesIndices = self::updateUsage($tokens, $importedClassesIndices, $index);
            }
        }

        self::clearImports($tokens, $importedClassesIndices);
    }

    /**
     * @return array<string, int|null>
     */
    private static function getImportCandidateIndices(Tokens $tokens, int $startIndex, int $endIndex): array
    {
        $importedClassesIndices = [];

        foreach (array_keys($tokens->findGivenKind(\T_USE, $startIndex, $endIndex)) as $index) {
            $classNameIndex = $tokens->getNextMeaningfulToken($index);
            \assert(\is_int($classNameIndex));

            if ($tokens[$classNameIndex]->isGivenKind(\T_NS_SEPARATOR)) {
                $classNameIndex = $tokens->getNextMeaningfulToken($classNameIndex);
                \assert(\is_int($classNameIndex));
            }

            $semicolonIndex = $tokens->getNextMeaningfulToken($classNameIndex);
            \assert(\is_int($semicolonIndex));

            if (!$tokens[$semicolonIndex]->equals(';')) {
                continue;
            }

            $importedClassesIndices[$tokens[$classNameIndex]->getContent()] = $classNameIndex;
        }

        return $importedClassesIndices;
    }

    /**
     * @param array<string, int|null> $importedClassesIndices
     *
     * @return array<string, int|null>
     */
    private static function updateComment(Tokens $tokens, array $importedClassesIndices, int $index): array
    {
        $content = $tokens[$index]->getContent();

        foreach ($importedClassesIndices as $importedClassName => $importedClassIndex) {
            $content = preg_replace(\sprintf('/\\b(?<!\\\\)%s(?!\\\\)\\b/', $importedClassName), '\\' . $importedClassName, $content);
            if ($importedClassIndex !== null && preg_match(\sprintf('/\\b(?<!\\\\)%s(?=\\\\)\\b/', $importedClassName), $content)) {
                $importedClassesIndices[$importedClassName] = null;
            }
        }

        if ($content !== $tokens[$index]->getContent()) {
            $tokens[$index] = new Token([\T_DOC_COMMENT, $content]);
        }

        return $importedClassesIndices;
    }

    /**
     * @param array<string, int|null> $importedClassesIndices
     *
     * @return array<string, int|null>
     */
    private static function updateUsage(Tokens $tokens, array $importedClassesIndices, int $index): array
    {
        if (!\in_array($tokens[$index]->getContent(), array_keys($importedClassesIndices), true)) {
            return $importedClassesIndices;
        }

        $prevIndex = $tokens->getPrevMeaningfulToken($index);
        \assert(\is_int($prevIndex));

        if ($tokens[$prevIndex]->isGivenKind([\T_CONST, \T_DOUBLE_COLON, \T_FUNCTION, \T_NS_SEPARATOR, \T_OBJECT_OPERATOR, \T_USE])) {
            return $importedClassesIndices;
        }

        $nextIndex = $tokens->getNextMeaningfulToken($index);
        \assert(\is_int($nextIndex));

        if ($tokens[$nextIndex]->isGivenKind(\T_NS_SEPARATOR)) {
            $importedClassesIndices[$tokens[$index]->getContent()] = null;

            return $importedClassesIndices;
        }

        $tokens->insertAt($index, new Token([\T_NS_SEPARATOR, '\\']));

        return $importedClassesIndices;
    }

    /**
     * @param array<string, int|null> $importedClassesIndices
     */
    private static function clearImports(Tokens $tokens, array $importedClassesIndices): void
    {
        foreach ($importedClassesIndices as $importedClassIndex) {
            if ($importedClassIndex === null) {
                continue;
            }
            $useIndex = $tokens->getPrevTokenOfKind($importedClassIndex, [[\T_USE]]);
            \assert(\is_int($useIndex));

            $semicolonIndex = $tokens->getNextTokenOfKind($importedClassIndex, [';']);
            \assert(\is_int($semicolonIndex));

            $tokens->clearRange($useIndex, $semicolonIndex);
            TokenRemover::removeWithLinesIfPossible($tokens, $useIndex);
        }
    }
}
