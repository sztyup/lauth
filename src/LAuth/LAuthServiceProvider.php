<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Events;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LaravelDoctrine\ORM\DoctrineManager;

class LAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(ProviderRegistry::class);
        $this->app->singleton(LAuth::class);

        // Hook into doctrine as early as possible
        $this->app->booting(function (Application $application) {
            /** @var DoctrineManager $doctrineManager */
            $doctrineManager = $application->make(DoctrineManager::class);

            $doctrineManager->addPaths([__DIR__ . '/Entities']);
            $doctrineManager->extendAll(function ($conf, $conn, EventManager $eventManager) use ($application) {
                $eventManager->addEventListener(
                    Events::loadClassMetadata,
                    $application->make(ProviderRegistry::class)
                );
            });
        });

        $this->mergeConfigFrom(
            __DIR__ . '/../config/lauth.php',
            'lauth'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/lauth.php' => config_path('lauth.php'),
        ], 'config');
    }
}
