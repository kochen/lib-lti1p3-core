<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Security\Jwt\Builder;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Configuration\ConfigurationFactory;
use OAT\Library\Lti1p3Core\Security\Jwt\Token;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use OAT\Library\Lti1p3Core\Util\Generator\IdGenerator;
use OAT\Library\Lti1p3Core\Util\Generator\IdGeneratorInterface;
use Throwable;

class Builder implements BuilderInterface
{
    /** @var ConfigurationFactory */
    private $factory;

    /** @var IdGeneratorInterface */
    private $generator;

    /** @var int */
    private $messageTtl;

    public function __construct(
        ?ConfigurationFactory $factory = null,
        ?IdGeneratorInterface $generator = null,
        int $messageTtl = MessagePayloadInterface::TTL
    ) {
        $this->factory = $factory ?? new ConfigurationFactory();
        $this->generator = $generator ?? new IdGenerator();
        $this->messageTtl = $messageTtl;
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function build(array $headers, array $claims, KeyInterface $key): TokenInterface
    {
        try {
            $now = Carbon::now()->setMicrosecond(0);
            $config = $this->factory->create($key);
            $builder = $config->builder();

            foreach ($headers as $headerName => $headerValue) {
                $builder = $builder->withHeader($headerName, $headerValue);
            }

            $claims = array_merge(
                $claims,
                [
                    MessagePayloadInterface::CLAIM_JTI => $this->generator->generate(),
                    MessagePayloadInterface::CLAIM_IAT => $now->toDateTimeImmutable(),
                    MessagePayloadInterface::CLAIM_NBF => $now->toDateTimeImmutable(),
                    MessagePayloadInterface::CLAIM_EXP => $now->addSeconds($this->messageTtl)->toDateTimeImmutable(),
                ]
            );
            foreach ($claims as $claimName => $claimValue) {
                switch ($claimName) {
                    case MessagePayloadInterface::CLAIM_JTI:
                        $builder = $builder->identifiedBy($claimValue);
                        break;
                    case MessagePayloadInterface::CLAIM_EXP:
                        $builder = $builder->expiresAt($claimValue);
                        break;
                    case MessagePayloadInterface::CLAIM_NBF:
                        $builder = $builder->canOnlyBeUsedAfter($claimValue);
                        break;
                    case MessagePayloadInterface::CLAIM_IAT:
                        $builder = $builder->issuedAt($claimValue);
                        break;
                    case MessagePayloadInterface::CLAIM_SUB:
                        $builder = $builder->relatedTo($claimValue);
                        break;
                    case MessagePayloadInterface::CLAIM_ISS:
                        $builder = $builder->issuedBy($claimValue);
                        break;
                    case MessagePayloadInterface::CLAIM_AUD:
                        if (is_array($claimValue)) {
                            foreach ($claimValue as $audience) {
                                $builder = $builder->permittedFor($audience);
                            }
                        } else {
                            $builder = $builder->permittedFor($claimValue);
                        }
                        break;
                    default:
                        $builder = $builder->withClaim($claimName, $claimValue);
                }
            }

            return new Token($builder->getToken($config->signer(), $config->signingKey()));
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot build token: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
