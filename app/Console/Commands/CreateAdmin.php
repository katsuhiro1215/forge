<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;

class CreateAdmin extends Command
{
    protected $signature = 'forge:create-admin {email} {--name=管理者}';

    protected $description = '管理画面（/admin）にログインする管理者を作成する';

    public function handle(): int
    {
        $email = $this->argument('email');
        if (Admin::where('email', $email)->exists()) {
            $this->error("{$email} はすでに登録されています。");

            return self::FAILURE;
        }

        $pw = password('パスワード（12文字以上）', required: true, validate: fn ($v) => strlen($v) < 12 ? '12文字以上にしてください' : null);
        Admin::create(['name' => $this->option('name'), 'email' => $email, 'password' => $pw]);
        $this->info("管理者を作成しました: {$email}");

        return self::SUCCESS;
    }
}
