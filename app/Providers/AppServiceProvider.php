<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    /**
     * 具名限流器，路由用 throttle:<名稱> 套用。
     * 定義集中在這裡，改額度不必翻路由檔。
     */
    private function configureRateLimiters(): void
    {
        // 登入：以 email + IP 為單位，避免單一 IP 對多組帳號暴力嘗試，
        // 也避免攻擊者用同一組 email 把別人鎖在門外。
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

        // 註冊：純以 IP 計算，擋批量建帳號。
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(3)
            ->by($request->ip()));
    }
}
