<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** 通常の購入プラン（1サイト） */
    public function singlePlan(): ?Plan
    {
        return $this->plans()->where('code', 'single')->where('status', 'active')->first();
    }

    public function demoUrl(): ?string
    {
        if (! $this->demo_path) {
            return null;
        }

        return rtrim(config('forge.demo_url'), '/').'/'.trim($this->demo_path, '/').'/';
    }
}
