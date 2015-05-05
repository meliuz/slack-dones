<?php

namespace App;

use Silex\Application;

class RoutesLoader
{
    private $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->instantiateControllers();

    }

    private function instantiateControllers()
    {
        $this->app['user.controller'] = $this->app->share(function () {
            return new Controllers\UserController($this->app['user.service'], $this->app['slack']);
        });
        $this->app['site.controller'] = $this->app->share(function () {
            return new Controllers\SiteController();
        });
    }

    public function bindRoutesToControllers()
    {
        // API
        $api = $this->app['controllers_factory'];
        $api->post('/user/get-dones', 'user.controller:getDones');

        $this->app->mount($this->app['api.endpoint'].'/'.$this->app['api.version'], $api);

        // Site
        $site = $this->app['controllers_factory'];
        $site->get('/', 'site.controller:index');

        $this->app->mount('/', $site);
    }
}

