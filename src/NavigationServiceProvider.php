<?php

namespace Inoplate\Navigation;

use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{  
    /**
     * @var boolean
     */
    protected $defer = true;

    /**
     * Register package
     * 
     * @return void
     */
    public function register()
    {
        $this->app->singleton('\Inoplate\Navigation\Navigation', 'Inoplate\Navigation\Navigation');
        $this->app->singleton('navigation', function($app){
            return new Navigation($app['authis'], $app['router'], $app['url'], $app['events']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['navigation'];
    }
}