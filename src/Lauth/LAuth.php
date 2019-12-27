<?php
declare(strict_types=1);

namespace Sztyup\Lauth;

use Doctrine\ORM\EntityManager;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Sztyup\Lauth\Entities\Account;
use Sztyup\Lauth\Entities\User;
use Sztyup\Lauth\Events\Login;
use Sztyup\Lauth\Exceptions\ProviderNotFound;

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
    }

    /**
     * @throws ProviderNotFound
     */
    public function buidProvider(string $provider): AbstractProvider
    {
        $config = $this->config->get("lauth.provider.$provider");

        if ($config === null) {
            throw new ProviderNotFound($provider);
        }

        try {
            return $this->container->make($config['class']);
        } catch (BindingResolutionException $exception) {
            throw new ProviderNotFound($provider);
        }
    }

    /**
     * @throws ProviderNotFound
     */
    public function handleProviderCallback(string $providerName): User
    {
        $provider = $this->buidProvider($providerName);

        $providerUser = $provider->callback();

        $account = $this->getAccountFromProviderUser($provider, $providerUser);

        $user = $this->getUserFromAccount($account, $providerUser);

        $this->em->flush();

        $this->manager->guard()->login($user);

        $this->dispatcher->dispatch(
            new Login($user, $providerUser)
        );

        return $user;
    }

    protected function getAccountFromProviderUser(ProviderInterface $provider, ProviderUser $user): Account
    {
        /** @var Account $account */
        $account = $this->em->getRepository(Account::class)->findOneBy([
            'providerUserId' => $user->providerId,
            'provider' => $provider->getName()
        ]);

        if ($account === null) {
            $account = $provider->createAccount($user);
        }

        return $account;
    }

    protected function getUserFromAccount(Account $socialAccount, ProviderUser $providerUser): User
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
            ->setName($providerUser->name)
            ->setEmail($providerUser->email)
        ;

        $this->em->persist($user);

        $socialAccount->setUser($user);

        return $user;
    }
}
