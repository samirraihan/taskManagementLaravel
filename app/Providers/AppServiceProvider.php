<?php

namespace App\Providers;

use App\Http\Controllers\Backend\PageController;
use App\Http\Controllers\Frontend\PageController as FrontendPageController;
use App\Models\Backend\ContactBranch;
use App\Models\Backend\ContactCommon;
use App\Models\Backend\Footer;
use App\Models\Backend\Header;
use App\Models\Backend\Menus;
use App\Models\Backend\Pages;
use App\Models\Backend\PagesCategories;
use App\Models\Frontend\Pages as FrontendPages;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
