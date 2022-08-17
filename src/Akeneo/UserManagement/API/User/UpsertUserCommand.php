<?php

declare(strict_types=1);

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Akeneo\UserManagement\API\User;

final class UpsertUserCommand
{
    public function __construct(
        public string $username,
        public string $password,
        public string $email,
        public string $type,
        public string $firstName,
        public string $lastName,
        public array $roleCodes,
        public array $groupCodes = [],
    ) {
    }
}
