<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

use DateTime;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sztyup\LAuth\Entities\Account;
use Sztyup\LAuth\Events\ProviderLogin;
use Sztyup\LAuth\Exceptions\InvalidStateException;

abstract class AbstractProvider implements ProviderInterface
{
    /** @var Request */
    protected $request;

    /** @var Client */
    protected $guzzle;

    /** @var EntityManager */
    protected $em;

    /** @var Dispatcher */
    protected $dispatcher;

    /** @var array */
    protected $config;

    public function __construct(
        Request $request,
        Client $guzzle,
        EntityManager $em,
        Dispatcher $dispatcher,
        array $config
    ) {
        $this->request    = $request;
        $this->guzzle     = $guzzle;
        $this->em         = $em;
        $this->dispatcher = $dispatcher;
        $this->config     = $config;
    }

    public function redirect(): RedirectResponse
    {
        $state = Str::random(32);

        $this->request->session()->put('state', $state);

        return RedirectResponse::create($this->redirectUrl($state));
    }

    /**
     * @throws InvalidStateException
     */
    public function callback(): Account
    {
        $this->checkState();

        $tokens = $this->getTokensFromCode($this->request->query->get('code'));

        $providerUser = $this->getUserByAccessToken($tokens->accessToken);

        $account = $this->matchExistingAccount($providerUser);

        if ($account === null) {
            $account = $this->createAccount($providerUser, $tokens);
            $account->setCreatedAt(new DateTime());
        } else {
            $this->updateAccount($account, $providerUser, $tokens);
        }

        $account->setUpdatedAt(new DateTime());
        $account->setLastSignedIn(new DateTime());

        $this->em->flush();

        if (!$this->dispatcher->until(new ProviderLogin($providerUser, $account))) {
            return null;
        }

        return $account;
    }

    public function refresh(Account $account): void
    {
        $tokens = $this->getTokensFromCode($account->getAccessToken());

        $providerUser = $this->getUserByAccessToken($tokens->accessToken);

        $this->updateAccount($account, $providerUser, $tokens);

        $account->setUpdatedAt(new DateTime());

        $this->em->flush();
    }

    protected function matchExistingAccount(ProviderUser $providerUser): ?Account
    {
        $query = sprintf(
            'SELECT acc FROM %s acc WHERE acc INSTANCE OF %s AND acc.providerUserId=:userId',
            Account::class,
            static::getAccountEntity()
        );

        $result = $this
            ->em
            ->createQuery($query)
            ->setParameter('userId', $providerUser->providerId)
            ->execute()
        ;

        if (empty($result)) {
            return null;
        }

        return $result[0];
    }

    abstract protected function createAccount(ProviderUser $providerUser, TokenResponse $tokens): Account;

    abstract protected function updateAccount(Account $account, ProviderUser $providerUser, TokenResponse $tokens): void;

    abstract protected function redirectUrl(string $state): string;

    /**
     * @throws InvalidStateException
     */
    protected function checkState(): void
    {
        $expected = $this->request->session()->pull('state');
        $given = $this->request->query->get('state');

        if ($expected !== $given) {
            throw new InvalidStateException($expected, $given);
        }
    }

    protected function getTokensFromCode(string $code): TokenResponse
    {
        $response = $this->guzzle->post($this->config['token_url'], [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'code' => $code,
                'redirect_uri' => $this->config['redirect_url'],
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        $return = new TokenResponse();
        $return->accessToken = Arr::get($response, 'access_token');
        $return->refreshToken = Arr::get($response, 'refresh_token');
        $return->accessTokenExpiration = Arr::get($response, 'expires_in');

        return $return;
    }

    abstract protected function getUserByAccessToken(string $accessToken): ProviderUser;
}
