<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\CustomFields\Tests\TestCase;

// Apply base test configuration to all tests
uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);

expect()->extend('toBeSameModel', fn (Model $model) => $this
    ->is($model)->toBeTrue());
