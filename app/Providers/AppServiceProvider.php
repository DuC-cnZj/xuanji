<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Configs\ChartValues;
use App\Configs\ChartValuesImp;
use App\Configs\Parser\ParserManger;
use App\Http\Middleware\LogOperations;
use Illuminate\Support\ServiceProvider;
use App\Services\Images\DockerImageManager;
use App\Services\Images\DockerImageInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ChartValuesImp::class, function () {
            return new ChartValues(new ParserManger());
        });
        $this->app->singleton(DockerImageInterface::class, function () {
            return new DockerImageManager();
        });
        $this->app->singleton(LogOperations::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Carbon::setLocale('zh');
    }
}
