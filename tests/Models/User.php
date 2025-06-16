<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;

class User extends Authenticatable
{
    use HasFactory;
    use UsesCustomFields;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
