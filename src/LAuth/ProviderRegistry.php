<?php

declare(strict_types=1);

namespace Sztyup\LAuth;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use LaravelDoctrine\ORM\DoctrineManager;
use Sztyup\LAuth\Entities\Account;

class ProviderRegistry
{
    /** @var Container */
    protected $container;

    /** @var DoctrineManager */
    protected $doctrineManager;

    /** @var Repository */
    protected $config;

    protected $providers = [];
    protected $entityPaths;

    public function __construct(Container $container, DoctrineManager $doctrineManager, Repository $config)
    {
        $this->container       = $container;
        $this->doctrineManager = $doctrineManager;
        $this->config          = $config;
    }

    public function register(string $name, string $providerClass): void
    {
        $this->providers[$name] = $providerClass;
        $this->doctrineManager->addPaths([$providerClass::getEntitiesPath()]);
    }

    public function getProvider($name): ProviderInterface
    {
        return $this->container->make(
            $this->providers[$name],
            [
                'config' => $this->config->get('lauth.providers.' . $name, [])
            ]
        );
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $metadata = $event->getClassMetadata();
        if ($metadata->getName() === Account::class) {
            $this->baseAccount($metadata);
        }
    }

    protected function baseAccount(ClassMetadata $metadata): void
    {
        $metadata->discriminatorMap = []; // Delete auto generated map
        foreach ($this->providers as $name => $class) {
            $metadata->addDiscriminatorMapClass($name, $class::getAccountEntity());
        }

        $userClass = $this->container->make(Repository::class)->get('lauth.user_class');

        $metadata->associationMappings['user']['targetEntity'] = $userClass;
    }
}
