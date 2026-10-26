<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Release;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function __invoke(Request $request, Release $release)
    {
        // ダウンロードできる有効なライセンスを、本人の持ち物の中から探す
        $license = $request->user()->licenses()
            ->where('product_id', $release->product_id)
            ->get()
            ->first(fn ($license) => $license->canDownload($release));

        abort_unless($license, 403);

        $disk = Storage::disk('local');
        abort_unless($release->file_path && $disk->exists($release->file_path), 404);

        Download::create([
            'license_id' => $license->id,
            'release_id' => $release->id,
            // 日次ソルト付きハッシュ（生IPは保存しない）
            'ip_hash' => hash('sha256', $request->ip().'|'.now()->toDateString().'|'.config('app.key')),
            'downloaded_at' => now(),
        ]);

        $name = $release->product->slug.'-'.$release->version.'.zip';

        return $disk->download($release->file_path, $name);
    }
}
