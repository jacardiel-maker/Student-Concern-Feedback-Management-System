<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'name_fil', 'is_urgent', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_urgent' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function concerns(): HasMany
    {
        return $this->hasMany(Concern::class);
    }

    public function label(): string
    {
        return app()->getLocale() === 'fil' && $this->name_fil ? $this->name_fil : $this->name;
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function urgent(Builder $query): Builder
    {
        return $query->where('is_urgent', true);
    }
}
