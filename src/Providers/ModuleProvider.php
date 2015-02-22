<?php
namespace TypiCMS\Modules\Menus\Providers;

use Config;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lang;
use TypiCMS\Modules\Menus\Models\Menu;
use TypiCMS\Modules\Menus\Repositories\CacheDecorator;
use TypiCMS\Modules\Menus\Repositories\EloquentMenu;
use TypiCMS\Services\Cache\LaravelCache;
use View;

class ModuleProvider extends ServiceProvider
{

    public function boot()
    {

        $this->mergeConfigFrom(
            __DIR__ . '/../config/config.php', 'typicms.menus'
        );

        $this->loadViewsFrom(__DIR__ . '/../resources/views/', 'menus');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'menus');

        $this->publishes([
            __DIR__ . '/../resources/views' => base_path('resources/views/vendor/menus'),
        ], 'views');
        $this->publishes([
            __DIR__ . '/../database' => base_path('database'),
        ], 'migrations');
        $this->publishes([
            __DIR__ . '/../../tests' => base_path('tests'),
        ], 'tests');

        AliasLoader::getInstance()->alias(
            'Menus',
            'TypiCMS\Modules\Menus\Facades\Facade'
        );
    }

    public function register()
    {

        $app = $this->app;

        /**
         * Register route service provider
         */
        $app->register('TypiCMS\Modules\Menus\Providers\RouteServiceProvider');

        /**
         * Sidebar view composer
         */
        $app->view->composer('core::admin._sidebar', 'TypiCMS\Modules\Menus\Composers\SideBarViewComposer');

        $app->bind('TypiCMS\Modules\Menus\Repositories\MenuInterface', function (Application $app) {
            $repository = new EloquentMenu(new Menu);
            if (! Config::get('app.cache')) {
                return $repository;
            }
            $laravelCache = new LaravelCache($app['cache'], ['menus', 'menulinks', 'pages'], 10);

            return new CacheDecorator($repository, $laravelCache);
        });

        /*
        |--------------------------------------------------------------------------
        | Get all menus.
        |--------------------------------------------------------------------------|
        */
        $this->app['TypiCMS.menus'] = $this->app->share(function (Application $app) {
            return $app->make('TypiCMS\Modules\Menus\Repositories\MenuInterface')->getAllMenus();
        });
    }
}
