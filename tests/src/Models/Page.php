<?php

namespace Relaticle\CustomFields\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Tests\Database\Factories\PageFactory;

class Page extends Model
{
    use HasFactory;

    protected static function newFactory(): PageFactory
    {
        return new PageFactory;
    }

    protected $guarded = [];

    protected $casts = [
        'json_content' => 'array',
    ];
}
