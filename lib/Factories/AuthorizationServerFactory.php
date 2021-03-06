<?php

/*
 * This file is part of the simplesamlphp-module-oidc.
 *
 * (c) Sergio Gómez <sergio@uco.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SimpleSAML\Modules\OpenIDConnect\Factories;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use OpenIDConnectServer\IdTokenResponse;
use SimpleSAML\Modules\OpenIDConnect\ClaimTranslatorExtractor;
use SimpleSAML\Modules\OpenIDConnect\Repositories\AccessTokenRepository;
use SimpleSAML\Modules\OpenIDConnect\Repositories\ClientRepository;
use SimpleSAML\Modules\OpenIDConnect\Repositories\ScopeRepository;
use SimpleSAML\Modules\OpenIDConnect\Repositories\UserRepository;
use SimpleSAML\Utils\Config;

class AuthorizationServerFactory
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;
    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;
    /**
     * @var ScopeRepository
     */
    private $scopeRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var AuthCodeGrant
     */
    private $authCodeGrant;
    /**
     * @var ImplicitGrant
     */
    private $implicitGrant;
    /**
     * @var RefreshTokenGrant
     */
    private $refreshTokenGrant;
    /**
     * @var \DateInterval
     */
    private $accessTokenDuration;
    /**
     * @var string|null
     */
    private $passPhrase;

    public function __construct(
        ClientRepository $clientRepository,
        AccessTokenRepository $accessTokenRepository,
        ScopeRepository $scopeRepository,
        UserRepository $userRepository,
        AuthCodeGrant $authCodeGrant,
        ImplicitGrant $implicitGrant,
        RefreshTokenGrant $refreshTokenGrant,
        \DateInterval $accessTokenDuration,
        string $passPhrase = null
    ) {
        $this->clientRepository = $clientRepository;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->scopeRepository = $scopeRepository;
        $this->userRepository = $userRepository;
        $this->authCodeGrant = $authCodeGrant;
        $this->implicitGrant = $implicitGrant;
        $this->refreshTokenGrant = $refreshTokenGrant;
        $this->accessTokenDuration = $accessTokenDuration;
        $this->passPhrase = $passPhrase;
    }

    public function build()
    {
        $privateKeyPath = Config::getCertPath('oidc_module.pem');
        $encryptionKey = Config::getSecretSalt();

        $authorizationServer = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            new CryptKey($privateKeyPath, $this->passPhrase),
            $encryptionKey,
            new IdTokenResponse($this->userRepository, new ClaimTranslatorExtractor())
        );

        $authorizationServer->enableGrantType(
            $this->authCodeGrant,
            $this->accessTokenDuration
        );

        $authorizationServer->enableGrantType(
            $this->implicitGrant,
            $this->accessTokenDuration
        );

        $authorizationServer->enableGrantType(
            $this->refreshTokenGrant,
            $this->accessTokenDuration
        );

        return $authorizationServer;
    }
}
