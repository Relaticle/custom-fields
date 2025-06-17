<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Strategy
    |--------------------------------------------------------------------------
    */
    'validation_strategy' => \Spatie\LaravelData\Support\Creation\ValidationStrategy::Always,

    /*
    |--------------------------------------------------------------------------
    | Transformation Depth
    |--------------------------------------------------------------------------
    */
    'max_transformation_depth' => null,
    'throw_when_max_depth_reached' => false,

    /*
    |--------------------------------------------------------------------------
    | Data Objects
    |--------------------------------------------------------------------------
    */
    'features' => [
        'cast_and_transform_iterables' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Normalizers
    |--------------------------------------------------------------------------
    */
    'normalizers' => [
        \Spatie\LaravelData\Normalizers\ModelNormalizer::class,
        \Spatie\LaravelData\Normalizers\FormRequestNormalizer::class,
        \Spatie\LaravelData\Normalizers\ArrayableNormalizer::class,
        \Spatie\LaravelData\Normalizers\ObjectNormalizer::class,
        \Spatie\LaravelData\Normalizers\ArrayNormalizer::class,
        \Spatie\LaravelData\Normalizers\JsonNormalizer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Transformers
    |--------------------------------------------------------------------------
    */
    'transformers' => [
        DateTimeInterface::class => \Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer::class,
        \Illuminate\Contracts\Support\Arrayable::class => \Spatie\LaravelData\Transformers\ArrayableTransformer::class,
        BackedEnum::class => \Spatie\LaravelData\Transformers\EnumTransformer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Casts
    |--------------------------------------------------------------------------
    */
    'casts' => [
        DateTimeInterface::class => \Spatie\LaravelData\Casts\DateTimeInterfaceCast::class,
        BackedEnum::class => \Spatie\LaravelData\Casts\EnumCast::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule Inferrers
    |--------------------------------------------------------------------------
    */
    'rule_inferrers' => [
        \Spatie\LaravelData\RuleInferrers\SometimesRuleInferrer::class,
        \Spatie\LaravelData\RuleInferrers\NullableRuleInferrer::class,
        \Spatie\LaravelData\RuleInferrers\RequiredRuleInferrer::class,
        \Spatie\LaravelData\RuleInferrers\BuiltInTypesRuleInferrer::class,
        \Spatie\LaravelData\RuleInferrers\AttributesRuleInferrer::class,
    ],
];
