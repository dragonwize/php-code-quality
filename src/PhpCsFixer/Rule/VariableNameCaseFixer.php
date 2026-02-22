<?php declare(strict_types=1);

namespace Dragonwize\PhpCodeQuality\PhpCsFixer\Rule;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class VariableNameCaseFixer implements FixerInterface
{
    public function getName(): string
    {
        return 'Dragonwize/variable_name_case';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Enforce camelCase for variable names.',
            [
                new CodeSample("<?php \$my_variable = 2;\n"),
            ],
            'Make sure to run a static analyzer after changes.',
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([\T_VARIABLE, \T_STRING_VARNAME]);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        if ($tokens->count() > 0 && $this->isCandidate($tokens) && $this->supports($file)) {
            $this->applyFix($file, $tokens);
        }
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function supports(\SplFileInfo $file): bool
    {
        return true;
    }

    private function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind([\T_VARIABLE, \T_STRING_VARNAME, \T_PROPERTY_C])) {
                continue;
            }

            $content = $token->getContent();

            // Skip $this and superglobals
            if ($this->shouldSkip($content)) {
                continue;
            }

            $newName = $this->toCamelCase($content);

            if ($content !== $newName) {
                $tokens[$index] = new Token([$token->getId(), $newName]);
            }
        }
    }

    private function shouldSkip(string $name): bool
    {
        // Skip specific variable names.
        if ($name === '$this') {
            return true;
        }

        if (\array_key_exists(ltrim($name, '$'), $GLOBALS)) {
            return true;
        }

        return false;
    }

    private function toCamelCase(string $variableName): string
    {
        return str_replace('_', '', ucwords(trim($variableName), '_ '));
    }
}
