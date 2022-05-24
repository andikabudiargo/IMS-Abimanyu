<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use DB;
use App\Models\Attribute;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $decimalPlaces = Attribute::where('attr_code','decimalPlaces')->value('attr_value');
        config(['globalParam.decimal' => $decimalPlaces]);
    }
}
