<?php

declare(strict_types=1);

namespace Akeneo\UserManagement\Infrastructure\Cli\Registry;

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class AuthenticatedAsAdminCommandRegistry
{
    /**
     * @var array<string, bool>
     */
    private array $commandsAuthenticatedAsAdminUser = [];

    public function registerCommand(string $commandName): void
    {
        $this->commandsAuthenticatedAsAdminUser[$commandName] = true;
    }

    public function isCommandAuthenticatedAsAdminUser(string $commandName): bool
    {
        return \array_key_exists($commandName, $this->commandsAuthenticatedAsAdminUser);
    }
}
