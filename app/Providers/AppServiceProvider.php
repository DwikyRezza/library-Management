<?php

namespace App\Providers;

use App\Contracts\PageWatermarker;
use App\Contracts\PdfPageRenderer;
use App\Services\NodePageWatermarker;
use App\Services\NodePdfPageRenderer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PdfPageRenderer::class, NodePdfPageRenderer::class);
        $this->app->bind(PageWatermarker::class, NodePageWatermarker::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
