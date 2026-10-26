<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\StripeWebhookController;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', [
        'ork' => Product::published()->where('slug', 'ork')->firstOrFail(),
        'news' => Post::published()->orderByDesc('published_at')->limit(3)->get(),
    ]);
})->name('home');

Route::view('/legal/tokushoho', 'legal.tokushoho')->name('legal.tokushoho');
Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/legal/refund', 'legal.refund')->name('legal.refund');

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{post:slug}', [NewsController::class, 'show'])->name('news.show');

Route::redirect('/theme', '/#collection');

Route::get('/theme/{product:slug}', function (Product $product) {
    abort_unless($product->type === 'theme' && $product->status === 'published', 404);
    abort_unless($product->slug === 'ork', 404); // 専用ビューがあるのはOrkのみ（他テーマは追加時に拡張）

    return view('theme.show', [
        'product' => $product,
        'plan' => $product->singlePlan() ?? abort(404),
        'demoUrl' => $product->demoUrl(),
    ]);
})->name('theme.show');

Route::post('/checkout/{product:slug}', [CheckoutController::class, 'start'])->name('checkout.start');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/webhook/stripe', StripeWebhookController::class)->name('webhook.stripe');

// 認証（会員登録は設けない。アカウントは購入時に自動作成される）
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [PasswordController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::get('/account/downloads/{release}', DownloadController::class)->name('account.download');
});
