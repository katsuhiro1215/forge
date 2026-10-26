<?php

namespace App\Filament\Resources\Licenses\Pages;

use App\Filament\Resources\Licenses\LicenseResource;
use App\Models\License;
use App\Models\Order;
use App\Models\Plan;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateLicense extends CreateRecord
{
    protected static string $resource = LicenseResource::class;

    protected static ?string $title = 'ライセンスを手動で付与';

    /** ライセンスは必ず注文に紐づく。手動付与は金額0円の注文（manual_）を作って結びつける */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $plan = Plan::findOrFail($data['plan_id']);

            $order = Order::create([
                'user_id' => $data['user_id'],
                'plan_id' => $plan->id,
                'stripe_session_id' => 'manual_'.bin2hex(random_bytes(8)),
                'amount_jpy' => 0,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return License::create($data + [
                'order_id' => $order->id,
                'site_limit' => $plan->site_limit,
                'status' => 'active',
            ]);
        });
    }
}
