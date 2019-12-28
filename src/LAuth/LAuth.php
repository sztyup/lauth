<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

use Doctrine\ORM\EntityManager;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Sztyup\LAuth\Entities\Account;
use Sztyup\LAuth\Entities\User;
use Sztyup\LAuth\Events\Login;
use Sztyup\LAuth\Exceptions\ProviderNotFound;

class LAuth
{
    /** @var Container */
    protected $container;

    /** @var EntityManager */
    protected $em;

    /** @var AuthManager */
    protected $manager;

    /** @var Dispatcher */
    protected $dispatcher;

    /** @var Repository */
    protected $config;

    /** @var Collection */
    protected $providers;

    public function __construct(
        Container $container,
        EntityManager $em,
        AuthManager $manager,
        Dispatcher $dispatcher,
        Repository $config
    ) {
        $this->container = $container;
        $this->em = $em;
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
        $this->config = $config;
        $this->providers = Collection::make();
    }

    public function addProvider(string $name, string $class)
    {
        $this->providers[$name] = $class;

        return $this;
    }

    /**
     * @throws ProviderNotFound
     */
    protected function buildProvider(string $provider): AbstractProvider
    {
        $class = $this->providers[$provider] ?? null;

        if ($class === null) {
            throw new ProviderNotFound($provider);
        }

        try {
            return $this->container->make($class);
        } catch (BindingResolutionException $exception) {
            throw new ProviderNotFound($provider);
        }
    }

    /**
     * @throws ProviderNotFound
     */
    public function redirectToProvider(string $providerName)
    {
        $provider = $this->buildProvider($providerName);

        return $provider->redirect();
    }

    /**
     * @throws ProviderNotFound
     * @throws Exceptions\InvalidStateException
     */
    public function handleProviderCallback(string $providerName): User
    {
        $provider = $this->buildProvider($providerName);

        $account = $provider->callback();

        $user = $this->getUserFromAccount($account);

        $this->em->flush();

        $this->manager->guard()->login($user);

        $this->dispatcher->dispatch(
            new Login($user)
        );

        return $user;
    }

    protected function getUserFromAccount(Account $socialAccount): User
    {
        // User already associated
        if ($socialAccount->getUser() !== null) {
            return $socialAccount->getUser();
        }

        // Create user if there are none

        $userClass = $this->config->get('user.class');
        /** @var User $user */
        $user = new $userClass();
        $user
            ->setName($socialAccount->getName())
            ->setEmail($socialAccount->getEmail())
        ;

        $this->em->persist($user);

        $socialAccount->setUser($user);

        return $user;
    }

    public function getUser(): ?UserInterface
    {
        return $this->manager->guard()->user();
    }
}
