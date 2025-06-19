<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Migrations;

use Illuminate\Database\Migrations\Migration;
use Relaticle\CustomFields\Contracts\CustomsFieldsMigrators;

abstract class CustomFieldsMigration extends Migration
{
    protected CustomsFieldsMigrators $migrator;

    abstract public function up();

    public function __construct()
    {
        $this->migrator = app(CustomsFieldsMigrators::class);
    }
}
