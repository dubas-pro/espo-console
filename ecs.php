<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\NoLeadingImportSlashFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoEmptyReturnFixer;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/bin/espo',
        __DIR__ . '/bootstrap.php',
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ]);

    $ecsConfig->ruleWithConfiguration(ArraySyntaxFixer::class, [
        'syntax' => 'short',
    ]);

    $ecsConfig->skip([
        NotOperatorWithSuccessorSpaceFixer::class,
        PhpdocNoEmptyReturnFixer::class
    ]);

    $ecsConfig->sets([
        SetList::SPACES,
        SetList::ARRAY,
        SetList::DOCBLOCK,
        SetList::PSR_12,
        SetList::STRICT,
        SetList::CLEAN_CODE,
    ]);

    $ecsConfig->rule(OrderedClassElementsFixer::class);
    $ecsConfig->rule(NoLeadingImportSlashFixer::class);
    $ecsConfig->rule(FullyQualifiedStrictTypesFixer::class);
};
