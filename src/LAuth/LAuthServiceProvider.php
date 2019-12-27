<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

use Illuminate\Support\ServiceProvider;
use LaravelDoctrine\ORM\DoctrineManager;

class LAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            __DIR__ . '/../config/lauth.php',
            'lauth'
        );
    }

    public function boot(DoctrineManager $manager): void
    {
        $this->publishes([
            __DIR__ . '/../config/lauth.php' => config_path('lauth.php'),
        ], 'config');

        $manager->addPaths([__DIR__ . '/Entities']);
    }
}