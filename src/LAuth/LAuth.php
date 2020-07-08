<?php

declare(strict_types=1);

namespace Sztyup\LAuth;

use Doctrine\ORM\EntityManager;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Sztyup\LAuth\Entities\Account;
use Sztyup\LAuth\Entities\User;
use Sztyup\LAuth\Events\Login;

use function get_class;

class LAuth
{
    /** @var EntityManager */
    protected $em;

    /** @var AuthManager */
    protected $manager;

    /** @var Dispatcher */
    protected $dispatcher;

    /** @var Repository */
    protected $config;

    /** @var ProviderRegistry */
    protected $providerRegistry;

    public function __construct(
        EntityManager $em,
        AuthManager $manager,
        Dispatcher $dispatcher,
        Repository $config,
        ProviderRegistry $providerRegistry
    ) {
        $this->em               = $em;
        $this->manager          = $manager;
        $this->dispatcher       = $dispatcher;
        $this->config           = $config;
        $this->providerRegistry = $providerRegistry;
    }

    public function redirectToProvider(string $providerName)
    {
        $provider = $this->providerRegistry->getProvider($providerName);

        return $provider->redirect();
    }

    public function handleProviderCallback(string $providerName, bool $forceRefresh = false): ?User
    {
        $provider = $this->providerRegistry->getProvider($providerName);

        $account = $provider->callback($forceRefresh);

        if ($account === null) {
            return null;
        }

        $user = $this->getUserFromAccount($account);

        $this->em->flush();

        $this->manager->guard()->login($user);

        $this->dispatcher->dispatch(
            new Login($user)
        );

        return $user;
    }

    public function refreshAccount(Account $account, bool $forceRefresh = false): Account
    {
        $provider = $this->getProviderForAccount($account);

        return $provider->refresh($account, $forceRefresh);
    }

    public function getProviderUser(Account $account, bool $forceRefresh = false): ProviderUser
    {
        $provider = $this->getProviderForAccount($account);

        return $provider->getProviderUser($account, $forceRefresh);
    }

    protected function getProviderForAccount(Account $account): ProviderInterface
    {
        $map          = $this->em->getClassMetadata(Account::class)->discriminatorMap;
        $providerName = array_search(get_class($account), $map, true);

        return $this->providerRegistry->getProvider($providerName);
    }

    protected function getUserFromAccount(Account $socialAccount): User
    {
        // User already associated
        if ($socialAccount->getUser() !== null) {
            return $socialAccount->getUser();
        }

        // Create user if there are none

        $userClass = $this->config->get('lauth.user_class');

        $existing = $this->em->getRepository($userClass)->findOneBy([
            'email' => $socialAccount->getEmail()
        ]);

        if ($existing) {
            $socialAccount->setUser($existing);

            return $existing;
        }

        /** @var User $user */
        $user = new $userClass();
        $user
            ->setName($socialAccount->getName())
            ->setEmail($socialAccount->getEmail())
        ;

        $this->em->persist($user);

        $socialAccount->setUser($user);

        $this->refreshAccount($socialAccount);

        return $user;
    }

    public function getUser(): ?UserInterface
    {
        return $this->manager->guard()->user();
    }
}
