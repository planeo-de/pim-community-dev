<?php

declare(strict_types=1);

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Akeneo\UserManagement\Application\Handler\User;

use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\UserManagement\API\User\UpsertUserCommand;
use Akeneo\UserManagement\API\User\UpsertUserHandlerInterface;
use Akeneo\UserManagement\API\ViolationsException;
use Akeneo\UserManagement\Component\Factory\UserFactory;
use Akeneo\UserManagement\Component\Model\UserInterface;
use Akeneo\UserManagement\Component\Repository\UserRepositoryInterface;
use Akeneo\UserManagement\Component\Updater\UserUpdater;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UpsertUserHandler implements UpsertUserHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserUpdater $userUpdater,
        private UserFactory $userFactory,
        private SaverInterface $userSaver,
        private ValidatorInterface $validator,
    ) {
    }

    public function handle(UpsertUserCommand $upsertUserCommand): void
    {
        /** @var UserInterface|null $user */
        $user = $this->userRepository->findOneBy(['username' => $upsertUserCommand->username]);

        if (null === $user) {
            $user = $this->userFactory->create();
            $this->defineType($user, $upsertUserCommand->type);
        }

        //TODO type should be validated

        $userPayload = [
            'username' => $upsertUserCommand->username,
            'password' => $upsertUserCommand->password,
            'first_name' => $upsertUserCommand->firstName,
            'last_name' => $upsertUserCommand->lastName,
            'email' => $upsertUserCommand->email,
            'groups' => $upsertUserCommand->userGroupCodes,
            'roles' => $upsertUserCommand->roleCodes,
        ];

        $this->userUpdater->update($user, $userPayload);

        $constraintViolations = $this->validator->validate($user);

        if (0 < $constraintViolations->count()) {
            throw new ViolationsException($constraintViolations);
        }

        $this->userSaver->save($user);
    }

    private function defineType(UserInterface $user, string $type): void
    {
        switch ($type) {
            case 'user':
                return;
            case 'api':
                $user->defineAsApiUser();

                return;
            case 'job':
                $user->defineAsJobUser();

                return;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid type "%s"', $type));
        }
    }
}
