<?php

namespace App\Core\Bases;

use Illuminate\Database\Eloquent\Builder;

trait HasActive
{


    protected static function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
        ]);
    }
}
