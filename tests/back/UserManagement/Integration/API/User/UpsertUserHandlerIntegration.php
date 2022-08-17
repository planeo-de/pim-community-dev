<?php

declare(strict_types=1);


/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace AkeneoTest\UserManagement\Integration\API\User;

use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\Integration\TestCase;
use Akeneo\UserManagement\API\User\UpsertUserCommand;
use Akeneo\UserManagement\API\ViolationsException;
use Akeneo\UserManagement\Application\Handler\User\UpsertUserHandler;
use Akeneo\UserManagement\Component\Model\UserInterface;

class UpsertUserHandlerIntegration  extends TestCase
{
    public function testItCreateAUserIfUsernameDoesNotExist()
    {
        $this->assertUserDoesNotExist();
        $this->upsertUser();
        $this->assertUserExist();
    }

    public function testItUpdateAUserIfUsernameDoesNotExist()
    {
        $this->assertUserDoesNotExist();
        $this->upsertUser();

        $this->assertUserExist();
        $this->upsertUser(
            email: 'another_email@gmail.com',
            firstName: 'Another first name',
            lastName: 'Another last name',
            groupCodes: ['IT support'],
            roleCodes: ['ROLE_ADMINISTRATOR']
        );

        $user = $this->getUser();
        $this->assertEquals('another_email@gmail.com', $user->getEmail());
        $this->assertEquals('Another first name', $user->getFirstName());
        $this->assertEquals('Another last name', $user->getLastName());
        $this->assertEquals(['IT support', 'All'], $user->getGroupNames());
        $this->assertEquals(['ROLE_ADMINISTRATOR'], $user->getRoles());
    }

    public function testItThrowsAnExceptionWhenWeUpdateTheUserType()
    {
        $this->expectException(ViolationsException::class);

        $this->upsertUser();
        $this->upsertUser(type: 'api');
    }

    public function testItThrowsAnExceptionWhenWeUpdateTheUserWithAnUnsupportedType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->upsertUser(type: 'unsupported_user_type');
    }

    public function testItThrowsAnExceptionWhenRoleCodeDoesNotExist()
    {
        $this->expectException(ViolationsException::class);
        $this->upsertUser(roleCodes: ['DO_NOT_EXIST']);
    }

    public function testItThrowsAnExceptionWhenGroupCodeDoesNotExist()
    {
        $this->expectException(ViolationsException::class);
        $this->upsertUser(groupCodes: ['does not exist']);
    }

    private function assertUserDoesNotExist(): void
    {
        $this->assertNull($this->getUser());
    }

    private function assertUserExist(): void
    {
        $this->assertNotNull($this->getUser());
    }

    private function getUser(): ?UserInterface
    {
        $repository = $this->get('pim_user.repository.user');

        return $repository->findOneBy(['username' => 'a_user']);
    }

    private function getHandler(): UpsertUserHandler
    {
        return $this->get(UpsertUserHandler::class);
    }

    protected function getConfiguration(): Configuration
    {
        return $this->catalog->useMinimalCatalog();
    }

    private function upsertUser(
        string $email = 'a_user@gmail.com',
        string $type = 'user',
        string $firstName = 'John',
        string $lastName = 'Doe',
        array $groupCodes = [],
        array $roleCodes = ['ROLE_USER']
    ) {
        $this->getHandler()->handle(
            new UpsertUserCommand(
                'a_user',
                'my_password',
                $email,
                $type,
                $firstName,
                $lastName,
                $roleCodes,
                $groupCodes
            )
        );
    }
}
