<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    protected $guarded = [];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** 例: ¥19,800 */
    public function priceLabel(): string
    {
        return '¥'.number_format($this->price_jpy);
    }
}
