<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Security\Oidc\Endpoint;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Request\LtiLaunchRequest;
use OAT\Library\Lti1p3Core\Message\Builder\MessageBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticationRequest;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class PlatformOidcLoginAuthenticator
{
    /** @var DeploymentRepositoryInterface */
    private $repository;

    /** @var UserAuthenticatorInterface */
    private $authenticator;

    /** @var LtiLaunchRequestBuilder */
    private $requestBuilder;

    /** @var MessageBuilder */
    private $messageBuilder;

    /** @var Signer */
    private $signer;

    /** @var Parser */
    private $parser;

    public function __construct(
        DeploymentRepositoryInterface $repository,
        UserAuthenticatorInterface $authenticator,
        LtiLaunchRequestBuilder $requestBuilder = null,
        MessageBuilder $messageBuilder = null,
        Parser $parser = null,
        Signer $signer = null
    ) {
        $this->repository = $repository;
        $this->authenticator = $authenticator;
        $this->requestBuilder = $requestBuilder ?? new LtiLaunchRequestBuilder();
        $this->messageBuilder = $messageBuilder ?? new MessageBuilder();
        $this->parser = $parser ?? new Parser();
        $this->signer = $signer ?? new Sha256();
    }

    /**
     * @throws LtiException
     */
    public function authenticate(ServerRequestInterface $request): LtiLaunchRequest
    {
        try {
            /** @var OidcAuthenticationRequest $oidcRequest */
            $oidcRequest = OidcAuthenticationRequest::fromServerRequest($request);

            $originalMessage = new LtiMessage($this->parser->parse($oidcRequest->getLtiMessageHint()));

            $deployment = $this->repository->find($originalMessage->getDeploymentId());

            if (null === $deployment) {
                throw new LtiException('Invalid message hint deployment id');
            }

            if (!$originalMessage->getToken()->verify($this->signer, $deployment->getPlatformKeyChain()->getPublicKey())) {
               throw new LtiException('Invalid message hint signature');
            }

            if ($originalMessage->getToken()->isExpired()) {
                throw new LtiException('Message hint expired');
            }

            $authenticationResult = $this->authenticator->authenticate($oidcRequest->getLoginHint());

            if (!$authenticationResult->isSuccess()) {
                throw new LtiException('User authentication failure');
            }

            if (!$authenticationResult->isAnonymous()) {
                return $this->requestBuilder->buildUserResourceLinkLtiLaunchRequest(
                    $originalMessage->getResourceLink(),
                    $deployment,
                    $authenticationResult->getUserIdentity(),
                    $originalMessage->getRoles()
                );
            }

            return $this->requestBuilder->buildResourceLinkLtiLaunchRequest(
                $originalMessage->getResourceLink(),
                $deployment,
                $originalMessage->getRoles()
            );

        } catch (LtiException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('OIDC login initiation failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
