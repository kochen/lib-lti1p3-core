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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Launch\Validator;

use OAT\Library\Lti1p3Core\Launch\Validator\LtiLaunchRequestValidationResult;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use PHPUnit\Framework\TestCase;

class LtiLaunchRequestValidationResultTest extends TestCase
{
    public function testGetRegistration(): void
    {
        $registrationMock = $this->createMock(RegistrationInterface::class);

        $subject = new LtiLaunchRequestValidationResult($registrationMock);

        $this->assertEquals($registrationMock, $subject->getRegistration());
    }

    public function testGetLtiMessage(): void
    {
        $ltiMessageMock = $this->createMock(LtiMessageInterface::class);

        $subject = new LtiLaunchRequestValidationResult(null, $ltiMessageMock);

        $this->assertEquals($ltiMessageMock, $subject->getLtiMessage());
    }

    public function testGetOidcState(): void
    {
        $oidcStateMock = $this->createMock(MessageInterface::class);

        $subject = new LtiLaunchRequestValidationResult(null, null, $oidcStateMock);

        $this->assertEquals($oidcStateMock, $subject->getOidcState());
    }

    public function testBehavior(): void
    {
        $subject = new LtiLaunchRequestValidationResult();

        $this->assertFalse($subject->hasError());

        $subject->addSuccess('success');

        $this->assertFalse($subject->hasError());

        $subject->setError('error');

        $this->assertTrue($subject->hasError());

        $this->assertEquals(['success'], $subject->getSuccesses());
        $this->assertEquals('error', $subject->getError());
    }
}
