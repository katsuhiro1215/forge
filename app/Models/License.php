<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'support_until' => 'datetime',
            'updates_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** 購入から1年以内に公開されたリリースまでは、期限後も永続的にダウンロードできる */
    public function canDownload(Release $release): bool
    {
        return $this->isActive()
            && $release->product_id === $this->product_id
            && $release->released_at !== null
            && $release->released_at->lte($this->updates_until);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
