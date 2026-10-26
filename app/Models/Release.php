<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Release extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['released_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        // リリースを削除したら配布ZIPも消す
        static::deleted(function (Release $release) {
            if ($release->file_path) {
                Storage::disk('local')->delete($release->file_path);
            }
        });
    }

    /** アップロードされたZIPのサイズとSHA-256を計算してデータに足す */
    public static function withFileMeta(array $data): array
    {
        $disk = Storage::disk('local');
        $path = $data['file_path'] ?? null;
        if ($path && $disk->exists($path)) {
            $data['size'] = $disk->size($path);
            $data['sha256'] = hash_file('sha256', $disk->path($path));
        }

        return $data;
    }
}
