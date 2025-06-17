<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\CustomFields\Tests\TestCase;

// Apply base test configuration to all tests
uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);
