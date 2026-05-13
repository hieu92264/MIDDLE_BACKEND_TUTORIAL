<?php

namespace App\Core\Bases;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_active'), true);
    }
}
