<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sztyup\LAuth\Entities\Account;
use Sztyup\LAuth\Exceptions\InvalidStateException;

abstract class AbstractProvider implements ProviderInterface
{
    /** @var Request */
    protected $request;

    /** @var Client */
    protected $guzzle;

    /** @var EntityManager */
    protected $em;

    /** @var array */
    protected $config;

    public function __construct(Request $request, Client $guzzle, EntityManager $em, array $config)
    {
        $this->request = $request;
        $this->guzzle  = $guzzle;
        $this->em      = $em;
        $this->config  = $config;
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

        $tokens = $this->getAccessTokenFromCode($this->request->query->get('code'));

        $providerUser = $this->getUserFromResponse(
            $this->getResponseFromToken($tokens->accessToken)
        );

        $account = $this->matchExistingAccount($providerUser);

        if ($account === null) {
            $account = $this->createAccount($providerUser);
        } else {
            $this->updateAccount($account, $providerUser);
        }

        $this->em->flush();

        return $account;
    }

    public function refresh(Account $account): void
    {
        $providerUser = $this->getUserFromResponse(
            $this->getResponseFromToken($account->getAccessToken())
        );

        $this->updateAccount($account, $providerUser);
    }

    protected function matchExistingAccount(ProviderUser $providerUser): ?Account
    {
        return $this->em->getRepository(Account::class)->findOneBy([
            'providerUserId' => $providerUser->providerId,
            'provider' => $this->getName()
        ]);
    }

    abstract protected function createAccount(ProviderUser $providerUser): Account;

    abstract protected function updateAccount(Account $account, ProviderUser $providerUser): void;

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

    protected function getAccessTokenFromCode(string $code): TokenResponse
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

    protected function getUserFromResponse(array $response): ProviderUser
    {
        $user = new ProviderUser();

        $user->providerId = $response['id'];
        $user->name = $response['name'];
        $user->email = $response['email'];
        $user->data = $response;

        return $user;
    }

    abstract protected function getResponseFromToken(string $accessToken): array;
}
