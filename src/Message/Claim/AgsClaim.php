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

namespace OAT\Library\Lti1p3Core\Message\Claim;

use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;

/**
 * @see https://www.imsglobal.org/spec/lti-ags/v2p0#capabilities-in-jwt-messages
 */
class AgsClaim implements MessageClaimInterface
{
    /** @var array */
    private $scopes;

    /** @var string */
    private $lineItemsCollectionUrl;

    /** @var string|null */
    private $lineItemUrl;

    public static function getClaimName(): string
    {
        return LtiMessageInterface::CLAIM_LTI_AGS;
    }

    public function __construct(array $scopes, string $lineItemsCollectionUrl, string $lineItemUrl = null)
    {
        $this->scopes = $scopes;
        $this->lineItemsCollectionUrl = $lineItemsCollectionUrl;
        $this->lineItemUrl = $lineItemUrl;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getLineItemsCollectionUrl(): string
    {
        return $this->lineItemsCollectionUrl;
    }

    public function getLineItemUrl(): ?string
    {
        return $this->lineItemUrl;
    }

    public function normalize(): array
    {
        return array_filter([
            'scope' => $this->scopes,
            'lineitems' => $this->lineItemsCollectionUrl,
            'lineitem' => $this->lineItemUrl
        ]);
    }

    public static function denormalize(array $claimData): AgsClaim
    {
        return new self(
            $claimData['scope'],
            $claimData['lineitems'],
            $claimData['lineitem'] ?? null
        );
    }
}
