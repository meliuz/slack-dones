<?php

namespace App;

use Silex\Application;

class ServicesLoader
{
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function bindServicesIntoContainer()
    {
        $this->app['user.service'] = $this->app->share(function () {
            return new Services\UserService($this->app['db']);
        });
    }
}

