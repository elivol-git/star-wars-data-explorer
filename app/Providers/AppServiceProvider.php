<?php

namespace App\Providers;

use App\Services\Llm\Clients\HuggingFaceClient;
use App\Services\Llm\Clients\LlmClientInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(LlmClientInterface::class, HuggingFaceClient::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (app()->environment('production')) {
			URL::forceScheme('https');
		}
    }
}
