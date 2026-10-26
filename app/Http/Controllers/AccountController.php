<?php

namespace App\Http\Controllers;

use App\Models\Release;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $licenses = $user->licenses()->with(['product', 'plan', 'order'])->latest()->get();

        $releases = Release::whereIn('product_id', $licenses->pluck('product_id'))
            ->whereNotNull('released_at')
            ->orderByDesc('released_at')
            ->get()
            ->groupBy('product_id');

        return view('account.show', compact('user', 'licenses', 'releases'));
    }
}
