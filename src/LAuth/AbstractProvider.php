<?php
declare(strict_types=1);

namespace Sztyup\LAuth;

use Doctrine\Common\Persistence\ObjectRepository;
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

    /** @var string */
    protected $clientId;

    /** @var string */
    protected $clientSecret;

    /** @var string */
    protected $redirectUrl;

    /** @var string */
    protected $tokenUrl;

    /** @var array */
    protected $scopes = [];

    public function __construct(Client $guzzle, array $config)
    {
        $this->guzzle = $guzzle;
        $this->clientId = Arr::get($config, 'client_id');
        $this->clientSecret = Arr::get($config, 'client_secret');
        $this->redirectUrl = Arr::get($config, 'redirect_url');
        $this->tokenUrl = Arr::get($config, 'token_url');
        $this->scopes = Arr::get($config, 'scopes');
    }

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
        $response = $this->guzzle->post($this->tokenUrl, [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'redirect_uri' => $this->redirectUrl,
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        $return = new TokenResponse();
        $return->accessToken = Arr::get($response, 'access_token');
        $return->refreshToken = Arr::get($response, 'refresh_token');
        $return->accessTokenExpiration = Arr::get($response, 'expires_in');

        return $return;
    }

    abstract protected function getUserFromResponse(array $response): ProviderUser;

    abstract protected function getResponseFromToken(string $accessToken): array;

    public function redirect(): RedirectResponse
    {
        $state = Str::random(32);

        $this->request->session()->put('state', $state);

        return RedirectResponse::create($this->redirectUrl($state));
    }

    /**
     * @throws InvalidStateException
     */
    public function callback(): ProviderUser
    {
        $this->checkState();

        $tokens = $this->getAccessTokenFromCode($this->request->query->get('code'));

        return $this->getUserFromResponse(
            $this->getResponseFromToken($tokens->accessToken)
        );
    }

    public function refresh(Account $account): ProviderUser
    {
        return $this->getUserFromResponse(
            $this->getResponseFromToken($account->getAccessToken())
        );
    }

    public function matchLocalAccount(ObjectRepository $repository, ProviderUser $providerUser)
    {
        return $repository->findOneBy([
            'email' => $providerUser->email
        ]);
    }
}
