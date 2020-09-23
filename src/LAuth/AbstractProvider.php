<?php

declare(strict_types=1);

namespace Sztyup\LAuth;

use DateTime;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\MessageInterface;
use Sztyup\LAuth\Entities\Account;
use Sztyup\LAuth\Events\ProviderLogin;
use Sztyup\LAuth\Events\ProviderUpdate;
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
    public function callback(bool $forceRefresh = false): ?Account
    {
        $this->checkState();

        $tokens = $this->getTokensFromCode($this->request->query->get('code'));

        $providerUser = $this->getUserByAccessToken($tokens->accessToken, $forceRefresh);

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

        $this->dispatcher->dispatch(new ProviderLogin($providerUser, $account));

        return $account;
    }

    public function refresh(Account $account, bool $forceRefresh = false): Account
    {
        $providerUser = $this->getProviderUser($account, $forceRefresh);

        $this->updateAccount($account, $providerUser, null);

        $account->setUpdatedAt(new DateTime());

        $this->em->flush();

        $this->dispatcher->dispatch(new ProviderUpdate($providerUser, $account));

        return $account;
    }

    public function getProviderUser(Account $account, bool $forceRefresh = false): ProviderUser
    {
        try {
            return $this->getUserByAccessToken($account->getAccessToken(), $forceRefresh);
        } catch (RequestException $exception) {
            if ($exception->getResponse() && $exception->getResponse()->getStatusCode() === 401) {
                $this->refreshTokens($account);

                return $this->getUserByAccessToken($account->getAccessToken(), $forceRefresh);
            }

            throw $exception;
        }
    }

    protected function refreshTokens(Account $account): void
    {
        if ($account->getRefreshToken() !== null) {
            $tokenResponse = $this->getTokensFromRefreshToken($account->getRefreshToken());

            if ($tokenResponse->accessToken !== null) {
                $account->setAccessToken($tokenResponse->accessToken);
            }

            if ($tokenResponse->refreshToken !== null) {
                $account->setRefreshToken($tokenResponse->refreshToken);
            }
        }
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

    abstract protected function updateAccount(
        Account $account,
        ProviderUser $providerUser,
        ?TokenResponse $tokens
    ): void;

    abstract protected function redirectUrl(string $state): string;

    /**
     * @throws InvalidStateException
     */
    protected function checkState(): void
    {
        $expected = $this->request->session()->pull('state');
        $given    = $this->request->query->get('state');

        if ($expected !== $given) {
            throw new InvalidStateException($expected, $given);
        }
    }

    protected function getTokensFromCode(string $code): TokenResponse
    {
        $response = $this->guzzle->post(
            $this->config['token_url'],
            [
                'headers'     => ['Accept' => 'application/json'],
                'form_params' => [
                    'client_id'     => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'code'          => $code,
                    'redirect_uri'  => $this->config['redirect_url'],
                ]
            ]
        );

        return $this->parseTokenResponse($response);
    }

    protected function getTokensFromRefreshToken(string $refreshToken): TokenResponse
    {
        $response = $this->guzzle->post(
            $this->config['token_url'],
            [
                'headers'     => ['Accept' => 'application/json'],
                'form_params' => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id'     => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                ]
            ]
        );

        return $this->parseTokenResponse($response);
    }

    protected function parseTokenResponse(MessageInterface $response): TokenResponse
    {
        $response = json_decode($response->getBody()->getContents(), true);

        $tokenResponse                        = new TokenResponse();
        $tokenResponse->accessToken           = Arr::get($response, 'access_token');
        $tokenResponse->refreshToken          = Arr::get($response, 'refresh_token');
        $tokenResponse->accessTokenExpiration = Arr::get($response, 'expires_in');

        return $tokenResponse;
    }

    abstract protected function getUserByAccessToken(string $accessToken, bool $forceRefresh = false): ProviderUser;
}
