<?php

declare(strict_types=1);

return [
    'preset' => 'laravel',
    'ide' => 'phpstorm',
    'exclude' => [
        'vendor',
        'tests/Fixtures',
        'tests/database',
        'build',
        'storage',
        'bootstrap/cache',
        '.phpunit.result.cache',
    ],
    'add' => [
        //  \Example\Metrics\ForbiddenSecurityIssues::class,
    ],
    'remove' => [
        // Code
        \SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff::class,
        \SlevomatCodingStandard\Sniffs\TypeHints\DisallowArrayTypeHintSyntaxSniff::class,
        \SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff::class,
        
        // Architecture
        \NunoMaduro\PhpInsights\Domain\Insights\ForbiddenDefineGlobalConstants::class,
        \NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses::class,
        
        // Style
        \PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff::class,
        \SlevomatCodingStandard\Sniffs\Commenting\DocCommentSpacingSniff::class,
    ],
    'config' => [
        // Code Quality
        \PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff::class => [
            'lineLimit' => 120,
            'absoluteLineLimit' => 160,
        ],
        \SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff::class => [
            'maxLinesLength' => 50,
        ],
        \SlevomatCodingStandard\Sniffs\Classes\ClassLengthSniff::class => [
            'maxLinesLength' => 500,
        ],
        \SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff::class => [
            'spacesCountAroundEqualsSign' => 0,
        ],
        \SlevomatCodingStandard\Sniffs\Commenting\UselessInheritDocCommentSniff::class => [
            'usefulAnnotations' => ['@throws', '@return', '@param'],
        ],
        
        // Architecture
        \NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh::class => [
            'maxComplexity' => 10,
        ],
        
        // Style
        \SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff::class => [
            'searchAnnotations' => true,
        ],
    ],
    'requirements' => [
        'min-quality' => 80,
        'min-complexity' => 85,
        'min-architecture' => 85,
        'min-style' => 90,
        'disable-security-check' => false,
    ],
    'threads' => null,
]; 